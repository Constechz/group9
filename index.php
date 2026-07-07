<?php
// Dashboard Page
// Almighty Driving School Management System
require_once 'db.php';
include 'header.php';

// Fetch KPIs
try {
    // 1. Total Students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $total_students = $stmt->fetchColumn();

    // 2. Total Payments (Revenue)
    $stmt = $pdo->query("SELECT SUM(amount) FROM payments");
    $total_revenue = $stmt->fetchColumn() ?: 0.00;

    // 3. Upcoming Scheduled Lessons
    $stmt = $pdo->query("SELECT COUNT(*) FROM lessons WHERE status = 'Scheduled'");
    $scheduled_lessons = $stmt->fetchColumn();

    // 4. Active Vehicles
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Active'");
    $active_vehicles = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_students = 0;
    $total_revenue = 0.00;
    $scheduled_lessons = 0;
    $active_vehicles = 0;
}

// Fetch Recent Registrations
$recent_registrations = [];
try {
    $stmt = $pdo->query("SELECT r.*, s.first_name, s.surname FROM registrations r JOIN students s ON r.student_id = s.student_id ORDER BY r.registration_id DESC LIMIT 4");
    $recent_registrations = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Lessons List
$recent_lessons = [];
try {
    $stmt = $pdo->query("SELECT l.*, s.first_name AS student_first, s.surname AS student_sur, i.instructor_name, v.vehicle_registration_no FROM lessons l JOIN registrations r ON l.registration_id = r.registration_id JOIN students s ON r.student_id = s.student_id JOIN instructors i ON l.instructor_id = i.instructor_id JOIN vehicles v ON l.vehicle_id = v.vehicle_id ORDER BY l.lesson_date DESC, l.start_time DESC LIMIT 4");
    $recent_lessons = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch monthly payments for Chart
$months = [];
$revenues = [];
try {
    $stmt = $pdo->query("SELECT DATE_FORMAT(payment_date, '%b %Y') as month, SUM(amount) as total FROM payments GROUP BY month ORDER BY MIN(payment_date) ASC");
    $monthly_data = $stmt->fetchAll();
    foreach ($monthly_data as $row) {
        $months[] = $row['month'];
        $revenues[] = $row['total'];
    }
} catch (Exception $e) {}

// Safe fallback for charts if no payments exist
if (empty($months)) {
    $months = ['June 2026'];
    $revenues = [$total_revenue];
}
?>

<!-- Welcome Banner Hero -->
<div class="welcome-hero no-print">
    <div class="welcome-hero-content">
        <h2 class="welcome-hero-title">Welcome back, <?php echo htmlspecialchars($active_manager['manager_name']); ?>!</h2>
        <p class="welcome-hero-subtitle">Role: <span style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($active_manager['manager_role']); ?></span> • Here is the operations status overview for Almighty Driving School.</p>
    </div>
</div>

<!-- Grid Stats Panel -->
<div class="grid-stats">
    <div class="card-stat indigo-glow">
        <div class="card-stat-header">
            <div class="card-stat-icon" style="background-color: var(--primary-light); color: var(--primary);">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <span class="trend-badge trend-neutral">
                <i class="fa-solid fa-arrow-trend-up"></i> +12%
            </span>
        </div>
        <div>
            <div class="stat-title">Total Enrolled Students</div>
            <div class="stat-value"><?php echo $total_students; ?></div>
        </div>
    </div>
    
    <div class="card-stat emerald-glow">
        <div class="card-stat-header">
            <div class="card-stat-icon" style="background-color: var(--success-light); color: var(--success);">
                <i class="fa-solid fa-cedi-sign"></i>
            </div>
            <span class="trend-badge trend-up">
                <i class="fa-solid fa-plus"></i> Live
            </span>
        </div>
        <div>
            <div class="stat-title">Total Revenue Collected</div>
            <div class="stat-value">GH¢<?php echo number_format($total_revenue, 2); ?></div>
        </div>
    </div>
    
    <div class="card-stat amber-glow">
        <div class="card-stat-header">
            <div class="card-stat-icon" style="background-color: var(--warning-light); color: var(--warning);">
                <i class="fa-solid fa-clock"></i>
            </div>
            <span class="trend-badge trend-warning">
                <i class="fa-regular fa-calendar-check"></i> Today
            </span>
        </div>
        <div>
            <div class="stat-title">Pending Lessons</div>
            <div class="stat-value"><?php echo $scheduled_lessons; ?></div>
        </div>
    </div>
    
    <div class="card-stat rose-glow">
        <div class="card-stat-header">
            <div class="card-stat-icon" style="background-color: var(--danger-light); color: var(--danger);">
                <i class="fa-solid fa-car"></i>
            </div>
            <span class="trend-badge trend-neutral">
                Active
            </span>
        </div>
        <div>
            <div class="stat-title">Fleet Vehicles Available</div>
            <div class="stat-value"><?php echo $active_vehicles; ?></div>
        </div>
    </div>
</div>

<!-- Main Dashboard Grid Layout -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 450px), 1fr)); gap: 32px; margin-bottom: 32px;">
    
    <!-- Chart Card -->
    <div class="table-container card-glass" style="padding: 24px;">
        <h3 style="margin-bottom: 20px; font-weight: 800; color: var(--text-primary); font-size: 1.1rem; letter-spacing: -0.25px;">
            <i class="fa-solid fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i>
            Revenue Trend Overview (GH¢)
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Quick Shortcuts Panel -->
    <div class="table-container" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin-bottom: 8px; font-weight: 800; color: var(--text-primary); font-size: 1.1rem; letter-spacing: -0.25px;">
                <i class="fa-solid fa-bolt" style="color: var(--warning); margin-right: 8px;"></i>
                Quick Actions
            </h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 24px; font-weight: 500;">
                Direct shortcuts for day-to-day administrative operations.
            </p>
        </div>
        
        <div class="action-grid">
            <a href="students.php?action=new" class="action-card">
                <div style="background-color: var(--primary-light); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                    <i class="fa-solid fa-user-plus" style="color: var(--primary); font-size: 1.2rem;"></i>
                </div>
                <span>Register Student</span>
            </a>
            <a href="lessons.php?action=new" class="action-card">
                <div style="background-color: rgba(124, 58, 237, 0.1); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                    <i class="fa-solid fa-calendar-plus" style="color: var(--accent); font-size: 1.2rem;"></i>
                </div>
                <span>Schedule Lesson</span>
            </a>
            <a href="payments.php?action=new" class="action-card">
                <div style="background-color: var(--success-light); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                    <i class="fa-solid fa-money-bill-wave" style="color: var(--success); font-size: 1.2rem;"></i>
                </div>
                <span>Receive Payment</span>
            </a>
            <a href="reports.php" class="action-card">
                <div style="background-color: #f1f5f9; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                    <i class="fa-solid fa-print" style="color: var(--text-secondary); font-size: 1.2rem;"></i>
                </div>
                <span>Print Reports</span>
            </a>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 450px), 1fr)); gap: 32px; margin-bottom: 32px;">
    
    <!-- Recent Registrations Card -->
    <div class="table-container">
        <div class="table-header-bar">
            <h3 class="table-title">
                <i class="fa-solid fa-id-card-clip" style="color: var(--primary); margin-right: 8px;"></i>
                Recent Enrollments
            </h3>
            <a href="registrations.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">View All</a>
        </div>
        
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course Name</th>
                        <th>Date Registered</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_registrations)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 32px;">No enrollments logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_registrations as $reg): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['surname']); ?></td>
                                <td style="font-weight: 500; color: var(--text-secondary);"><?php echo htmlspecialchars($reg['course_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></td>
                                <td>
                                    <?php if ($reg['balance'] <= 0.00): ?>
                                        <span class="badge badge-success"><span class="status-dot success"></span>Fully Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><span class="status-dot warning"></span>Bal GHC<?php echo number_format($reg['balance'], 0); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Lessons Agenda Card -->
    <div class="table-container">
        <div class="table-header-bar">
            <h3 class="table-title">
                <i class="fa-solid fa-calendar-check" style="color: var(--accent); margin-right: 8px;"></i>
                Upcoming & Recent Lessons
            </h3>
            <a href="lessons.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">View Agenda</a>
        </div>
        
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Student & Instructor</th>
                        <th>Date & Time</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_lessons)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 32px;">No lessons scheduled yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_lessons as $lesson): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars($lesson['student_first'] . ' ' . $lesson['student_sur']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 500;">Instructor: <?php echo htmlspecialchars($lesson['instructor_name']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($lesson['lesson_date'])); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 500;"><?php echo date('h:i A', strtotime($lesson['start_time'])) . ' - ' . date('h:i A', strtotime($lesson['end_time'])); ?></div>
                                </td>
                                <td style="font-weight: 600; color: var(--text-secondary);"><?php echo htmlspecialchars($lesson['vehicle_registration_no']); ?></td>
                                <td>
                                    <?php if ($lesson['status'] == 'Completed'): ?>
                                        <span class="badge badge-success"><span class="status-dot success"></span>Completed</span>
                                    <?php elseif ($lesson['status'] == 'Scheduled'): ?>
                                        <span class="badge badge-info"><span class="status-dot info"></span>Scheduled</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><span class="status-dot danger"></span>Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Render Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Create elegant gradient fill
    const primaryGradient = ctx.createLinearGradient(0, 0, 0, 300);
    primaryGradient.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
    primaryGradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?php echo json_encode($revenues); ?>,
                backgroundColor: primaryGradient,
                borderColor: '#4f46e5',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#fff',
                pointHoverRadius: 7,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0b1329',
                    titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: '700' },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 13, weight: '600' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    border: { display: false },
                    grid: { 
                        color: '#e2e8f0',
                        tickColor: 'transparent',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                        callback: function(value) {
                            return 'GH¢' + value;
                        }
                    }
                },
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                    }
                }
            }
        }
    });
});
</script>

<?php 
// Open register modal if directed from shortcuts
if (isset($_GET['action']) && $_GET['action'] == 'new') {
    echo "<script>document.addEventListener('DOMContentLoaded', () => { openModal('registerModal'); });</script>";
}
include 'footer.php'; 
?>
