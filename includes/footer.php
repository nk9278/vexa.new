<?php
// File: /includes/footer.php
?>
    </main>

    <!-- Global Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto pb-16 sm:pb-0">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> Powered by <?php echo APP_NAME; ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bottom Mobile Navigation -->
    <div class="sm:hidden">
        <?php include __DIR__ . '/mobile-menu.php'; ?>
    </div>

</body>
</html>
