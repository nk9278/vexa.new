<?php
// File: /employee/task-comment.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkAuth(['Employee']);

$pdo = getDbConnection();
$user_id = $_SESSION['user_id'];
$submission_id = isset($_GET['sub_id']) ? filter_var($_GET['sub_id'], FILTER_VALIDATE_INT) : 0;

if (!$submission_id) {
    redirect('/employee/tasks.php');
}

// Fetch Submission and Verify Ownership
$stmt = $pdo->prepare("
    SELECT ts.*, t.task_name
    FROM task_submissions ts
    JOIN tasks t ON ts.task_id = t.id
    WHERE ts.id = :id AND ts.employee_id = :employee_id AND t.deleted_at IS NULL
");
$stmt->execute(['id' => $submission_id, 'employee_id' => $user_id]);
$submission = $stmt->fetch();

if (!$submission) {
    redirect('/employee/tasks.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $comment_text = sanitizeInput($_POST['comment_text'] ?? '');
        $voice_note_path = null;

        if (empty($comment_text) && empty($_FILES['voice_note']['name'])) {
            $error = "You must provide text or a voice note.";
        }

        // Handle Voice Note Error State
        if (!$error && isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['voice_note']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['voice_note']['error'] === UPLOAD_ERR_FORM_SIZE) {
                $error = "Voice note exceeds the server's maximum upload limit.";
            } elseif ($_FILES['voice_note']['error'] !== UPLOAD_ERR_OK) {
                $error = "An error occurred during voice note upload (Code: " . $_FILES['voice_note']['error'] . ").";
            }
        }

        // Handle Voice Note Upload Success State
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
                    $voice_note_path = '/uploads/voice_notes/' . $filename;
                } else {
                    $error = "Failed to upload voice note.";
                }
            }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO task_comments (task_id, submission_id, user_id, comment_text, voice_note_path)
                    VALUES (:task_id, :submission_id, :user_id, :comment_text, :voice_note_path)
                ");
                $stmt->execute([
                    'task_id' => $submission['task_id'],
                    'submission_id' => $submission_id,
                    'user_id' => $user_id,
                    'comment_text' => $comment_text,
                    'voice_note_path' => $voice_note_path
                ]);

                $_SESSION['success_msg'] = "Comment added successfully.";
                redirect('/employee/task-comment.php?sub_id=' . $submission_id);
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all comments for this submission
$stmt = $pdo->prepare("
    SELECT tc.*, u.full_name, r.name as role_name
    FROM task_comments tc
    JOIN users u ON tc.user_id = u.id
    JOIN roles r ON u.role_id = r.id
    WHERE tc.submission_id = :submission_id
    ORDER BY tc.created_at ASC
");
$stmt->execute(['submission_id' => $submission_id]);
$comments = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <div class="flex items-center mb-4">
        <a href="task-view.php?id=<?php echo $submission['task_id']; ?>" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold leading-tight text-gray-900">Feedback: <?php echo htmlspecialchars($submission['task_name']); ?></h1>
    </div>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="mx-4 sm:mx-6 mb-4 bg-green-50 text-green-600 p-3 rounded-lg text-sm">
        <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
    </div>
<?php endif; ?>

<div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-2 gap-6 mb-16">

    <!-- Submission Content & Comments -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Your Submission #<?php echo $submission['revision_count'] + 1; ?></h3>
            <p class="text-sm text-gray-500 mb-4">Status:
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                    <?php
                    if($submission['status'] === 'Approved') echo 'bg-green-100 text-green-800';
                    elseif($submission['status'] === 'Revision Required') echo 'bg-red-100 text-red-800';
                    else echo 'bg-yellow-100 text-yellow-800';
                    ?>
                ">
                    <?php echo htmlspecialchars($submission['status']); ?>
                </span>
            </p>

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mt-4 min-h-[100px]">
                <?php if ($submission['submission_text']): ?>
                    <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($submission['submission_text']); ?></p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic">No text provided.</p>
                <?php endif; ?>
            </div>

            <?php if ($submission['file_path']): ?>
                <div class="mt-4">
                    <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        View Attachment
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6">
            <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Conversation History</h3>
            <?php if (count($comments) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="p-4 rounded-lg <?php echo $comment['user_id'] == $user_id ? 'bg-indigo-50 ml-8' : 'bg-gray-50 mr-8'; ?>">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comment['full_name']); ?> <span class="text-xs text-gray-500 font-normal">(<?php echo htmlspecialchars($comment['role_name']); ?>)</span></span>
                                <span class="text-xs text-gray-500"><?php echo date('d-m-Y h:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <?php if ($comment['comment_text']): ?>
                                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                            <?php endif; ?>
                            <?php if ($comment['voice_note_path']): ?>
                                <div class="mt-2">
                                    <audio controls class="h-8 w-full max-w-xs">
                                        <source src="<?php echo htmlspecialchars($comment['voice_note_path']); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500 text-center py-4">No comments yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Add Comment Form -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Add a Note</h3>
            </div>

            <form method="POST" action="task-comment.php?sub_id=<?php echo $submission_id; ?>" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-500 p-3 rounded-lg mb-4 text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Comment</label>
                        <textarea name="comment_text" rows="4" placeholder="Write your note here..." class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
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

                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-3 rounded-xl hover:bg-indigo-700 transition shadow-sm font-medium">
                        Post Comment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
