<?php
// File: /includes/navbar.php

// Simple desktop navigation
// Highlight current page logic can be added later
?>
<?php if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Super Admin'): ?>
    <a href="/super-admin/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="/super-admin/agencies.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'agenc') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Agencies
    </a>
<?php elseif (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Agency Owner'): ?>
    <a href="/agency/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="/agency/managers.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'manager') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Managers
    </a>
    <a href="/agency/employees.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'employee') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Employees
    </a>
    <a href="/agency/clients.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'client') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Clients
    </a>
    <a href="/agency/projects.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'project') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Projects
    </a>
    <a href="/agency/payments.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'payment') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Payments
    </a>
    <a href="/agency/reports.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'report') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Reports
    </a>
<?php elseif (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Manager'): ?>
    <a href="/manager/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="/manager/clients.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'client') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Clients
    </a>
    <a href="/manager/projects.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'project') !== false && strpos($_SERVER['REQUEST_URI'], 'project-payment.php') === false && strpos($_SERVER['REQUEST_URI'], 'project-team.php') === false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Projects
    </a>
    <a href="/manager/payments.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'payment') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Payments
    </a>
<?php else: ?>
    <a href="#" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="#" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Clients
    </a>
    <a href="#" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Projects
    </a>
<?php endif; ?>
<a href="/logout.php" class="border-transparent text-red-500 hover:border-red-300 hover:text-red-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
    Logout
</a>
