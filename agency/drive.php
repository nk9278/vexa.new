<?php
// File: /agency/drive.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/auth.php';

checkAuth(['Agency Owner']);

// UI Only - No API Logic
$is_connected = false; // Toggle this manually to see UI states if needed

include __DIR__ . '/header.php';
?>

<div class="px-4 py-5 sm:px-6">
    <h1 class="text-3xl font-bold leading-tight text-gray-900">Google Drive Integration</h1>
    <p class="text-sm text-gray-500 mt-2">Connect your agency's Google Drive to store and organize project files automatically.</p>
</div>

<div class="px-4 sm:px-6 mt-4 max-w-3xl">
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-8 text-center">
            <!-- Drive Logo Placeholder -->
            <svg class="mx-auto h-16 w-16 mb-4" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                <path d="m6.6 66.85 22.35 11.15c2.4 1.2 5.4.15 6.6-2.25l22.95-45.75L37.15 6.6l-29.4 57.9c-1.05 2.1-.15 4.95 1.85 6.1l-3-3.75z" fill="#0066da"/>
                <path d="m71.8 66.85-22.35 11.15c-2.4 1.2-5.4.15-6.6-2.25l-22.95-45.75L41.25 6.6l29.4 57.9c1.05 2.1.15 4.95-1.85 6.1l3-3.75z" fill="#00ac47"/>
                <path d="m80.55 20.7-22.35-11.15c-2.4-1.2-5.4-.15-6.6 2.25L28.65 57.55 49.05 87.4l29.4-57.9c1.05-2.1.15-4.95-1.85-6.1l3.95-2.7z" fill="#ea4335"/>
                <path d="M49.05 87.4 28.65 57.55l-22.95 45.75c-1.2 2.4-.15 5.4 2.25 6.6l22.35 11.15c2.1 1.05 4.95.15 6.1-1.85l12.6-25.2v-6.6z" fill="#00832d"/>
                <path d="m37.15 6.6 20.4 29.85 22.95-45.75c1.2-2.4.15-5.4-2.25-6.6L55.9 2.95c-2.1-1.05-4.95-.15-6.1 1.85L37.15 30v-23.4z" fill="#2684fc"/>
                <path d="M41.25 6.6 20.85 36.45 7.75 6.6h55.65c2.4 0 4.35 1.95 4.35 4.35v11.25L41.25 6.6z" fill="#ffba00"/>
            </svg>

            <?php if ($is_connected): ?>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Connected</h3>
                <p class="text-sm text-gray-500 mb-6">Your Google Drive is successfully linked. Folder status is active.</p>
                <button type="button" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">
                    Disconnect Drive
                </button>
            <?php else: ?>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Not Connected</h3>
                <p class="text-sm text-gray-500 mb-6">Link your Google account to enable cloud storage features.</p>
                <button type="button" class="inline-flex justify-center items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition">
                    Connect Google Drive
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
