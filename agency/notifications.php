<?php
// File: /agency/notifications.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

// UI Only placeholder
include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Notifications</h1>
    <p class="text-sm text-gray-500 mt-2">Task updates, project updates, payment updates, and manager updates.</p>
</div>

<div class="px-4 sm:px-6 mt-4 max-w-4xl">
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <ul role="list" class="divide-y divide-gray-200">
            <?php
            $notifications = [
                ['title' => 'New Payment Received', 'desc' => 'Client "Acme Corp" paid $5,000 for SEO Project.', 'time' => '2 hours ago', 'icon_color' => 'text-green-500', 'bg_color' => 'bg-green-100'],
                ['title' => 'Project Completed', 'desc' => 'Manager "John Doe" marked "Website Redesign" as complete.', 'time' => '5 hours ago', 'icon_color' => 'text-blue-500', 'bg_color' => 'bg-blue-100'],
                ['title' => 'Task Update', 'desc' => 'Employee finished design assets.', 'time' => '1 day ago', 'icon_color' => 'text-purple-500', 'bg_color' => 'bg-purple-100'],
            ];
            foreach ($notifications as $note):
            ?>
            <li class="p-4 hover:bg-gray-50 transition">
                <div class="flex space-x-3">
                    <div class="flex-shrink-0">
                        <span class="h-10 w-10 rounded-full <?php echo $note['bg_color']; ?> flex items-center justify-center">
                            <svg class="h-5 w-5 <?php echo $note['icon_color']; ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            <?php echo $note['title']; ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?php echo $note['desc']; ?>
                        </p>
                    </div>
                    <div class="flex-shrink-0 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $note['time']; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
