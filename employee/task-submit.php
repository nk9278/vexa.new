<?php
// File: /employee/task-submit.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/notification_functions.php';

checkAuth(['Employee']);

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$task_id) {
    redirect('/employee/tasks.php');
}

// Fetch Task Details & Verify assignment
$stmt = $pdo->prepare("
    SELECT t.id, t.project_id, t.task_name, t.status, pa.role as my_role
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN project_assignments pa ON t.project_id = pa.project_id AND pa.employee_id = :employee_id
    WHERE t.id = :id AND ta.employee_id = :employee_id AND t.deleted_at IS NULL
");
$stmt->execute(['id' => $task_id, 'employee_id' => $user_id]);
$task = $stmt->fetch();

if (!$task) {
    redirect('/employee/tasks.php');
}

// Must be in a state allowing submission
if (!in_array($task['status'], ['In Progress', 'Revision Required'])) {
    $_SESSION['error_msg'] = "You cannot submit work unless the task is In Progress or Revision Required.";
    redirect('/employee/task-view.php?id=' . $task_id);
}

// Get the latest revision count for this employee and task
$stmt = $pdo->prepare("SELECT MAX(revision_count) as max_rev FROM task_submissions WHERE task_id = :task_id AND employee_id = :employee_id");
$stmt->execute(['task_id' => $task_id, 'employee_id' => $user_id]);
$current_revision = $stmt->fetchColumn() ?? -1; // -1 if no submissions yet
$new_revision_count = $current_revision + 1;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $submission_text = sanitizeInput($_POST['submission_text'] ?? '');
        $file_path = null;
        $voice_note_path = null;

        // Ensure at least one thing is submitted
        if (empty($submission_text) && empty($_FILES['work_file']['name']) && empty($_FILES['voice_note']['name'])) {
            $error = "You must provide text, a voice note, or a file attachment.";
        }

        // Handle File Upload
        if (!$error && isset($_FILES['work_file']) && $_FILES['work_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/submissions/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Use strict whitelist
            $allowed_file_exts = ['zip', 'rar', 'jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'mp4', 'mov', 'psd', 'ai'];
            $file_info = pathinfo($_FILES['work_file']['name']);
            $ext = strtolower($file_info['extension'] ?? '');

            if ($_FILES['work_file']['size'] > 50 * 1024 * 1024) { // 50MB limit
                $error = "File size exceeds 50MB limit.";
            } elseif (!in_array($ext, $allowed_file_exts)) {
                $error = "Invalid file type. Allowed: " . implode(', ', $allowed_file_exts);
            } else {
                $filename = uniqid('sub_', true) . '.' . $ext;
                $full_local_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['work_file']['tmp_name'], $full_local_path)) {
                    // Try to upload to Google Drive
                    $drive_result = uploadToGoogleDrive($_SESSION['agency_id'], $task['project_id'], $user_id, $task['my_role'] ?? 'Other', $full_local_path, $_FILES['work_file']['name'], $_FILES['work_file']['size']);

                    if ($drive_result && isset($drive_result['drive_link'])) {
                        $file_path = $drive_result['drive_link']; // Replace local path with drive link
                    } else {
                        $file_path = '/uploads/submissions/' . $filename; // Fallback to local
                    }
                } else {
                    $error = "Failed to upload work file.";
                }
            }
        }

        // Handle Voice Note Upload
        if (!$error && isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] === UPLOAD_ERR_OK) {
            $allowed_audio = ['mp3', 'wav', 'ogg', 'm4a', 'webm'];
            $file_info = pathinfo($_FILES['voice_note']['name']);
            $ext = strtolower($file_info['extension'] ?? '');

            if ($_FILES['voice_note']['size'] > 10 * 1024 * 1024) { // 10MB limit for audio
                $error = "Voice note exceeds 10MB limit.";
            } elseif (!in_array($ext, $allowed_audio)) {
                $error = "Invalid audio format.";
            } else {
                $upload_dir = __DIR__ . '/../uploads/voice_notes/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $filename = uniqid('vn_', true) . '.' . $ext;
                $full_local_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['voice_note']['tmp_name'], $full_local_path)) {
                    // Try to upload to Google Drive
                    $drive_result = uploadToGoogleDrive($_SESSION['agency_id'], $task['project_id'], $user_id, 'Other', $full_local_path, $_FILES['voice_note']['name'], $_FILES['voice_note']['size']);

                    if ($drive_result && isset($drive_result['drive_link'])) {
                        $voice_note_path = $drive_result['drive_link']; // Replace local path with drive link
                    } else {
                        $voice_note_path = '/uploads/voice_notes/' . $filename; // Fallback to local
                    }
                } else {
                    $error = "Failed to upload voice note.";
                }
            }
        }

        if (!$error) {
            $pdo->beginTransaction();
            try {
                // Insert Submission
                $stmt = $pdo->prepare("
                    INSERT INTO task_submissions (task_id, employee_id, submission_text, file_path, status, revision_count)
                    VALUES (:task_id, :employee_id, :submission_text, :file_path, 'Pending Review', :revision_count)
                ");
                $stmt->execute([
                    'task_id' => $task_id,
                    'employee_id' => $user_id,
                    'submission_text' => $submission_text,
                    'file_path' => $file_path,
                    'revision_count' => $new_revision_count
                ]);
                $submission_id = $pdo->lastInsertId();

                // If Voice Note was provided, attach it via task_comments
                if ($voice_note_path) {
                    $stmt = $pdo->prepare("
                        INSERT INTO task_comments (task_id, submission_id, user_id, comment_text, voice_note_path)
                        VALUES (:task_id, :submission_id, :user_id, 'Voice note attached to submission', :voice_note_path)
                    ");
                    $stmt->execute([
                        'task_id' => $task_id,
                        'submission_id' => $submission_id,
                        'user_id' => $user_id,
                        'voice_note_path' => $voice_note_path
                    ]);
                }

                // Update Task Status to Waiting For Approval
                $stmt = $pdo->prepare("UPDATE tasks SET status = 'Waiting For Approval' WHERE id = :task_id");
                $stmt->execute(['task_id' => $task_id]);

                // Log and notify CRM
                $stmt_crm = $pdo->prepare("SELECT crm_id, project_id FROM tasks WHERE id = :task_id");
                $stmt_crm->execute(['task_id' => $task_id]);
                $task_info = $stmt_crm->fetch();
                if ($task_info) {
                    $agency_id = $_SESSION['agency_id'];
                    logActivity($agency_id, $user_id, 'Work Submitted', "Revision #" . ($new_revision_count + 1) . " submitted.", $task_info['project_id'], null, $voice_note_path);
                    logAudit($agency_id, $user_id, 'Employee', "Submitted work for task ($task_id)", $task_info['project_id']);
                    createNotification($task_info['crm_id'], 'Submission Received', "Work submitted for task: " . $task['task_name'], "/crm/submission-review.php?id=$submission_id");
                }

                $pdo->commit();
                $_SESSION['success_msg'] = "Work submitted successfully and is pending review.";
                redirect('/employee/task-view.php?id=' . $task_id);
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center mb-4">
        <a href="task-view.php?id=<?php echo $task_id; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Submit Work: <?php echo htmlspecialchars($task['task_name']); ?></h1>
    </div>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-3xl">
        <form method="POST" action="task-submit.php?id=<?php echo $task_id; ?>" enctype="multipart/form-data" class="p-6 sm:p-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <?php if ($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
                <p class="text-sm text-blue-800">You are submitting <strong>Revision #<?php echo $new_revision_count + 1; ?></strong> for this task. Submitting work will change the task status to <em>Waiting For Approval</em>.</p>
            </div>

            <div class="space-y-6">

                <?php
                $role = $task['my_role'] ?? '';
                $text_label = "Submission Notes / Link";
                $text_placeholder = "Add text, links to external docs (like Google Drive), or general notes about your work...";
                $file_label = "File Attachment (Optional)";
                $file_subtext = "Images, Videos, Docs (Max size depends on server)";

                if ($role === 'Sales') {
                    $text_label = "Meeting/Visit Notes & Updates";
                    $text_placeholder = "Document your visit status, meeting outcomes, or generic notes...";
                    $file_label = "Upload Documents (Optional)";
                    $file_subtext = "PDF, DOCX, Images of contracts/proposals";
                } elseif ($role === 'Photographer') {
                    $text_label = "Shoot Notes & Link to RAW Files";
                    $text_placeholder = "Provide a Google Drive link for heavy RAW/Video files, and add shoot notes...";
                    $file_label = "Upload Photos / Videos (Optional)";
                    $file_subtext = "JPG, PNG, MP4 (Watch out for upload size limits)";
                } elseif ($role === 'Graphic Designer') {
                    $text_label = "Design Explanation & Links";
                    $text_placeholder = "Explain design choices, or link to Figma/Canva source files...";
                    $file_label = "Upload Design Files / Source Files";
                    $file_subtext = "JPG, PNG, PDF, PSD, AI (Zip heavy source files)";
                } elseif ($role === 'Video Editor') {
                    $text_label = "Editing Notes & Video Link";
                    $text_placeholder = "Provide a Drive/Vimeo link to the final video, plus processing notes...";
                    $file_label = "Upload Edited Video / Project Files";
                    $file_subtext = "MP4, ZIP (Watch out for upload size limits)";
                } elseif ($role === 'Content Writer') {
                    $text_label = "Content Draft & References";
                    $text_placeholder = "Write the content here, or paste a link to Google Docs...";
                    $file_label = "Upload Content Documents";
                    $file_subtext = "DOCX, PDF, TXT";
                } elseif ($role === 'Content Approval') {
                    $text_label = "Review Notes & Feedback";
                    $text_placeholder = "Provide detailed review notes. Approve or request revisions via text...";
                    $file_label = "Upload Reviewed/Annotated Documents";
                    $file_subtext = "PDF, DOCX with tracked changes";
                } elseif ($role === 'Web Developer') {
                    $text_label = "Deployment Notes & Links";
                    $text_placeholder = "Paste staging URL, GitHub PR link, or deployment notes...";
                    $file_label = "Upload Code Package / Screenshots";
                    $file_subtext = "ZIP, JPG, PNG";
                }
                ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo $text_label; ?></label>
                    <textarea name="submission_text" rows="5" placeholder="<?php echo $text_placeholder; ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo $file_label; ?></label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="work_file" type="file" class="sr-only">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500"><?php echo $file_subtext; ?></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Voice Note (Optional)</label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-6 h-6 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                                <p class="text-xs text-gray-500">Click to upload audio file</p>
                            </div>
                            <input type="file" name="voice_note" accept="audio/*" class="hidden" />
                        </label>
                    </div>
                </div>

            </div>

            <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
                <a href="task-view.php?id=<?php echo $task_id; ?>" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded-full hover:bg-gray-50 transition mr-3">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition shadow-sm">Submit Work</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
