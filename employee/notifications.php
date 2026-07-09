<?php
// File: /employee/notifications.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Employee']);

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Notifications</h1>
</div>

<div class="px-4 sm:px-6 mb-16">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-4xl">
        <ul class="divide-y divide-gray-200">
            <!-- Dummy Notification 1 -->
            <li class="p-6 hover:bg-gray-50 transition cursor-pointer">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">Task Assigned</p>
                        <p class="text-sm text-gray-500">You have been assigned a new task: "Website Update". Due in 3 days.</p>
                        <p class="text-xs text-gray-400 mt-1">10 minutes ago</p>
                    </div>
                    <div>
                        <span class="inline-block w-2 h-2 bg-indigo-600 rounded-full"></span>
                    </div>
                </div>
            </li>

            <!-- Dummy Notification 2 -->
            <li class="p-6 hover:bg-gray-50 transition cursor-pointer">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">Revision Requested</p>
                        <p class="text-sm text-gray-500">CRM has requested changes for "Ad Copywriting". Please review the feedback.</p>
                        <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                    </div>
                </div>
            </li>

            <!-- Dummy Notification 3 -->
            <li class="p-6 hover:bg-gray-50 transition cursor-pointer">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">Task Approved</p>
                        <p class="text-sm text-gray-500">Your submission for "Logo Redesign" was approved. Task is now completed.</p>
                        <p class="text-xs text-gray-400 mt-1">Yesterday</p>
                    </div>
                </div>
            </li>

            <!-- Dummy Notification 4 -->
            <li class="p-6 hover:bg-gray-50 transition cursor-pointer">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-yellow-100">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">Deadline Reminder</p>
                        <p class="text-sm text-gray-500">Task "Weekly Report" is due tomorrow.</p>
                        <p class="text-xs text-gray-400 mt-1">2 days ago</p>
                    </div>
                </div>
            </li>
        </ul>
        <div class="p-4 border-t border-gray-200 bg-gray-50 text-center">
            <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">View All Notifications</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
