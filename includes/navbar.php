<?php
// File: /includes/navbar.php

// Simple desktop navigation
// Highlight current page logic can be added later
?>
<?php if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Super Admin'): ?>
    <a href="<?php echo BASE_URL; ?>/super-admin/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="<?php echo BASE_URL; ?>/super-admin/agencies.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'agenc') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Agencies
    </a>
    <a href="<?php echo BASE_URL; ?>/reports/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/reports') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Reports
    </a>
    <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/notifications') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Notifications
    </a>
    <a href="<?php echo BASE_URL; ?>/files/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/files') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Files
    </a>
<?php elseif (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Agency Owner'): ?>
    <a href="<?php echo BASE_URL; ?>/agency/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="<?php echo BASE_URL; ?>/agency/managers.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'manager') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Managers
    </a>
    <a href="<?php echo BASE_URL; ?>/agency/employees.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'employee') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Employees
    </a>
    <a href="<?php echo BASE_URL; ?>/agency/clients.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'client') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Clients
    </a>
    <a href="<?php echo BASE_URL; ?>/agency/projects.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'project') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Projects
    </a>
    <a href="<?php echo BASE_URL; ?>/agency/payments.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'payment') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Payments
    </a>
    <a href="<?php echo BASE_URL; ?>/agency/reports.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'report') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Reports
    </a>
    <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/notifications') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Notifications
    </a>
    <a href="<?php echo BASE_URL; ?>/files/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/files') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Files
    </a>
<?php elseif (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Manager'): ?>
    <a href="<?php echo BASE_URL; ?>/manager/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="<?php echo BASE_URL; ?>/manager/clients.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'client') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Clients
    </a>
    <a href="<?php echo BASE_URL; ?>/manager/projects.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'project') !== false && strpos($_SERVER['REQUEST_URI'], 'project-payment.php') === false && strpos($_SERVER['REQUEST_URI'], 'project-team.php') === false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Projects
    </a>
    <a href="<?php echo BASE_URL; ?>/manager/payments.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'payment') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Payments
    </a>
    <a href="<?php echo BASE_URL; ?>/reports/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/reports') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Reports
    </a>
    <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/notifications') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Notifications
    </a>
    <a href="<?php echo BASE_URL; ?>/files/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/files') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Files
    </a>
<?php elseif (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'CRM'): ?>
    <a href="<?php echo BASE_URL; ?>/crm/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="<?php echo BASE_URL; ?>/crm/clients.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'client') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Clients
    </a>
    <a href="<?php echo BASE_URL; ?>/crm/projects.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'project') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Projects
    </a>
    <a href="<?php echo BASE_URL; ?>/crm/tasks.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'task') !== false && strpos($_SERVER['REQUEST_URI'], 'task-submissions.php') === false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Tasks
    </a>
    <a href="<?php echo BASE_URL; ?>/reports/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/reports') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Reports
    </a>
    <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/notifications') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Notifications
    </a>
    <a href="<?php echo BASE_URL; ?>/files/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/files') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Files
    </a>
<?php elseif (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Employee'): ?>
    <a href="<?php echo BASE_URL; ?>/employee/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="<?php echo BASE_URL; ?>/employee/tasks.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'task') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        My Tasks
    </a>
    <a href="<?php echo BASE_URL; ?>/employee/projects.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'project') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        My Projects
    </a>
    <a href="<?php echo BASE_URL; ?>/employee/clients.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], 'client') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        My Clients
    </a>
    <a href="<?php echo BASE_URL; ?>/reports/dashboard.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/reports') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Performance
    </a>
    <a href="<?php echo BASE_URL; ?>/notifications/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/notifications') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Notifications
    </a>
    <a href="<?php echo BASE_URL; ?>/files/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/files') !== false ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Files
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
<a href="<?php echo BASE_URL; ?>/logout.php" class="border-transparent text-red-500 hover:border-red-300 hover:text-red-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
    Logout
</a>
