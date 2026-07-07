<?php
// Shared Header Template
// Almighty Driving School Management System
require_once 'db.php';
session_start();

// Authentication Gate: Check if manager is authenticated
if (!isset($_SESSION['manager_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch active manager profile dynamically
try {
    $stmt = $pdo->prepare("SELECT * FROM managers WHERE manager_id = ?");
    $stmt->execute([$_SESSION['manager_id']]);
    $active_manager = $stmt->fetch();
} catch (Exception $e) {
    $active_manager = null;
}

if (!$active_manager) {
    // Session is invalid (e.g. manager deleted)
    header("Location: logout.php");
    exit;
}

// Set active page for navigation highlight
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almighty Driving School | Database Management System</title>
    
    <!-- Link CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- FontAwesome for Premium Icons -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <!-- Chart.js for beautiful charts -->
    <script src="assets/chartjs/chart.js"></script>
</head>
<body>

    <!-- Mobile Navigation Top Bar -->
    <div class="mobile-top-bar no-print">
        <div class="mobile-logo">
            <div class="mobile-logo-icon">
                <i class="fa-solid fa-car-side"></i>
            </div>
            <span class="mobile-logo-text">Almighty Driving</span>
        </div>
        <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar no-print">
        <div class="sidebar-logo">
            <div class="sidebar-logo-text">Almighty Driving</div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'students.php') ? 'active' : ''; ?>">
                <a href="students.php">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'registrations.php') ? 'active' : ''; ?>">
                <a href="registrations.php">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Registrations</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
                <a href="payments.php">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'lessons.php') ? 'active' : ''; ?>">
                <a href="lessons.php">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Lessons</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'instructors.php') ? 'active' : ''; ?>">
                <a href="instructors.php">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Instructors</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'vehicles.php') ? 'active' : ''; ?>">
                <a href="vehicles.php">
                    <i class="fa-solid fa-car"></i>
                    <span>Vehicles</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Reports Hub</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-profile" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($active_manager['manager_name']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($active_manager['manager_role']); ?></span>
                </div>
                <a href="logout.php" style="color: #ef4444; font-size: 1.1rem; padding: 4px; display: flex; align-items: center;" title="Log Out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        
        <!-- Top Bar (Header) -->
        <div class="top-bar no-print">
            <div>
                <h1 class="page-title">
                    <?php 
                    switch ($current_page) {
                        case 'index.php': echo 'Operations Dashboard'; break;
                        case 'students.php': echo 'Student Registry'; break;
                        case 'registrations.php': echo 'Course Enrollments'; break;
                        case 'payments.php': echo 'Billing & Payments'; break;
                        case 'lessons.php': echo 'Lesson Scheduler'; break;
                        case 'instructors.php': echo 'Instructors Team'; break;
                        case 'vehicles.php': echo 'School Fleet'; break;
                        case 'reports.php': echo 'Reports & Analytics'; break;
                        default: echo 'Management System';
                    }
                    ?>
                </h1>
                <p class="page-subtitle">Almighty Driving School Database Project System</p>
            </div>
            
            <div class="top-bar-actions">
                <div class="system-date">
                    <i class="fa-regular fa-calendar-check"></i> 
                    <span><?php echo date('l, d M Y'); ?></span>
                </div>
            </div>
        </div>
