<?php
session_start();

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Simple Router
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Authentication Check
if (!isLoggedIn() && $page !== 'login' && $page !== 'signup') {
    header('Location: index.php?page=login');
    exit;
}

// Logout Handling
if ($page === 'logout') {
    if (isset($_SESSION['user'])) {
        log_security_event($_SESSION['user']['UserID'], 'Logout', 'Success', 'User logged out');
    }
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Allowed pages list
$allowed_pages = ['dashboard', 'requisitions', 'inventory', 'purchase-orders', 'receiving', 'warehouse', 'issuance', 'adjustments', 'reports', 'settings', 'profile', 'login', 'signup', 'process_issuance', 'receive_items'];

// Page Title Handling
$pageTitles = [
    'dashboard' => 'Dashboard',
    'requisitions' => 'Requisitions',
    'purchase-orders' => 'Purchase Orders',
    'receiving' => 'Receiving',
    'inventory' => 'Inventory',
    'warehouse' => 'Warehouse Management',
    'issuance' => 'Issuance',
    'adjustments' => 'Adjustments',
    'reports' => 'Reports',
    'settings' => 'Settings',
    'profile' => 'Profile',
    'login' => 'Sign In',
    'signup' => 'Sign Up'
];

$pageTitle = isset($pageTitles[$page]) ? $pageTitles[$page] : 'Pharmacy System';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uswag Iloilo City Pharmacy - <?php echo $pageTitle; ?></title>
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        display: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0d9488', // Teal 600
                        'primary-hover': '#0f766e', // Teal 700
                        secondary: '#64748b', // Slate 500
                        success: '#10b981', // Emerald 500
                        warning: '#f59e0b', // Amber 500
                        danger: '#ef4444', // Red 500
                        info: '#3b82f6', // Blue 500
                    }
                }
            }
        }
    </script>
    <!-- Tailwind Forms Plugin -->
    <style type="text/tailwindcss">
        @layer utilities {
            .form-input, .form-textarea, .form-select, .form-multiselect, .form-checkbox, .form-radio {
                @apply block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:bg-slate-700 dark:border-slate-600 dark:text-white;
            }
        }
    </style>
    
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Custom scrollbar to match the original app feel */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Check for saved user preference, either in localStorage or system preferences
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 font-sans antialiased h-screen flex overflow-hidden">

    <?php if (isLoggedIn()): ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden relative">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8 transition-opacity duration-300 opacity-0 animate-fade-in">
                <?php 
                $file = "pages/{$page}.php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Page not found: $page</div>";
                }
                ?>
            </main>
        </div>

    <?php else: ?>
        <main class="w-full h-full overflow-auto">
            <?php 
            $file = "pages/{$page}.php";
            if (file_exists($file)) {
                include $file;
            } else {
                include 'pages/login.php';
            }
            ?>
        </main>
    <?php endif; ?>

</body>
</html>
