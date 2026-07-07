<?php
// Reports Hub & Business Analytics
// Almighty Driving School Management System
require_once 'db.php';

// Fetch data for all 7 reports
try {
    // 1. Student Registrations Report
    $stmt1 = $pdo->query("
        SELECT r.*, s.first_name, s.surname, s.telephone, s.national_id, m.manager_name 
        FROM registrations r 
        JOIN students s ON r.student_id = s.student_id 
        JOIN managers m ON r.manager_id = m.manager_id 
        ORDER BY r.registration_date DESC
    ");
    $report_registrations = $stmt1->fetchAll();

    // 2. Student Payments Report
    $stmt2 = $pdo->query("
        SELECT p.*, r.course_name, s.first_name, s.surname, r.balance 
        FROM payments p 
        JOIN registrations r ON p.registration_id = r.registration_id 
        JOIN students s ON r.student_id = s.student_id 
        ORDER BY p.payment_date DESC
    ");
    $report_payments = $stmt2->fetchAll();

    // 3. Lesson Schedule Report (Active Agenda)
    $stmt3 = $pdo->query("
        SELECT l.*, s.first_name, s.surname, i.instructor_name, v.vehicle_registration_no 
        FROM lessons l 
        JOIN registrations r ON l.registration_id = r.registration_id 
        JOIN students s ON r.student_id = s.student_id 
        JOIN instructors i ON l.instructor_id = i.instructor_id 
        JOIN vehicles v ON l.vehicle_id = v.vehicle_id 
        ORDER BY l.lesson_date ASC, l.start_time ASC
    ");
    $report_lessons = $stmt3->fetchAll();

    // 4. Outstanding Balances Report
    $stmt4 = $pdo->query("
        SELECT r.*, s.first_name, s.surname, s.telephone, s.email 
        FROM registrations r 
        JOIN students s ON r.student_id = s.student_id 
        WHERE r.balance > 0 
        ORDER BY r.balance DESC
    ");
    $report_balances = $stmt4->fetchAll();

    // 5. Completed Lessons Report
    $stmt5 = $pdo->query("
        SELECT l.*, s.first_name, s.surname, i.instructor_name, v.vehicle_registration_no 
        FROM lessons l 
        JOIN registrations r ON l.registration_id = r.registration_id 
        JOIN students s ON r.student_id = s.student_id 
        JOIN instructors i ON l.instructor_id = i.instructor_id 
        JOIN vehicles v ON l.vehicle_id = v.vehicle_id 
        WHERE l.status = 'Completed' 
        ORDER BY l.lesson_date DESC
    ");
    $report_completed_lessons = $stmt5->fetchAll();

    // 6. Vehicle Assignment / Usage Report
    $stmt6 = $pdo->query("
        SELECT v.vehicle_registration_no, v.vehicle_type, v.status, COUNT(l.lesson_id) as total_lessons 
        FROM vehicles v 
        LEFT JOIN lessons l ON v.vehicle_id = l.vehicle_id 
        GROUP BY v.vehicle_id 
        ORDER BY total_lessons DESC
    ");
    $report_vehicles = $stmt6->fetchAll();

    // 7. Instructor Assignment / Load Report
    $stmt7 = $pdo->query("
        SELECT i.instructor_name, i.license_type, i.telephone, COUNT(l.lesson_id) as total_lessons 
        FROM instructors i 
        LEFT JOIN lessons l ON i.instructor_id = l.instructor_id 
        GROUP BY i.instructor_id 
        ORDER BY total_lessons DESC
    ");
    $report_instructors = $stmt7->fetchAll();

} catch (Exception $e) {
    die("Error loading report analytics: " . $e->getMessage());
}

include 'header.php';
?>

<div class="tabs-container">
    <!-- Tab Navigation bar (Hidden on print) -->
    <ul class="tabs-nav no-print">
        <li class="tab-nav-item active" onclick="switchTab(event, 'tab-registrations')">
            <i class="fa-solid fa-id-card-clip"></i> Registrations
        </li>
        <li class="tab-nav-item" onclick="switchTab(event, 'tab-payments')">
            <i class="fa-solid fa-file-invoice-dollar"></i> Payments Log
        </li>
        <li class="tab-nav-item" onclick="switchTab(event, 'tab-schedule')">
            <i class="fa-solid fa-calendar-days"></i> Master Schedule
        </li>
        <li class="tab-nav-item" onclick="switchTab(event, 'tab-outstanding')">
            <i class="fa-solid fa-scale-unbalanced-stroke"></i> Receivables
        </li>
        <li class="tab-nav-item" onclick="switchTab(event, 'tab-completed')">
            <i class="fa-solid fa-circle-check"></i> Completed Lessons
        </li>
        <li class="tab-nav-item" onclick="switchTab(event, 'tab-vehicles')">
            <i class="fa-solid fa-car"></i> Vehicle Usage
        </li>
        <li class="tab-nav-item" onclick="switchTab(event, 'tab-instructors')">
            <i class="fa-solid fa-user-tie"></i> Instructor Load
        </li>
    </ul>

    <!-- ==========================================
         REPORT 1: STUDENT REGISTRATION REPORT
         ========================================== -->
    <div id="tab-registrations" class="tab-content active">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Student Registration Report</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Track daily student enrollments, course selections, and contact registries to estimate seasonal onboarding loads.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Date Registered</th>
                        <th>Student Name</th>
                        <th>Ghana Card ID</th>
                        <th>Registered Course</th>
                        <th>Assigned Manager</th>
                        <th>Commence Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_registrations as $row): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['registration_date'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></td>
                            <td><code><?php echo htmlspecialchars($row['national_id']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['manager_name']); ?></td>
                            <td><?php echo $row['commencement_date'] ? date('M d, Y', strtotime($row['commencement_date'])) : '<em>Pending</em>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         REPORT 2: STUDENT PAYMENT REPORT
         ========================================== -->
    <div id="tab-payments" class="tab-content">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Student Payment Ledger Report</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Reconciles daily transactions, logs cash flow, and monitors individual balance updates to prevent bookkeeping errors.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Payment Date</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Method</th>
                        <th>Amount Paid</th>
                        <th>Remaining Bal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_payments as $row): ?>
                        <tr>
                            <td><code>#PAY-<?php echo $row['payment_id']; ?></code></td>
                            <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['payment_type']); ?></td>
                            <td style="color: var(--success); font-weight: 700;">GH¢<?php echo number_format($row['amount'], 2); ?></td>
                            <td style="color: var(--text-secondary);">GH¢<?php echo number_format($row['balance_after_payment'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         REPORT 3: MASTER SCHEDULE REPORT
         ========================================== -->
    <div id="tab-schedule" class="tab-content">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Master Lesson Schedule Report</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Provides a calendar overview of training schedules to optimize asset allocation and prevent dual-booking conflicts.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Student</th>
                        <th>Assigned Instructor</th>
                        <th>Fleet Vehicle</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_lessons as $row): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo date('M d, Y', strtotime($row['lesson_date'])); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></td>
                            <td><?php echo htmlspecialchars($row['instructor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['vehicle_registration_no']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['lesson_type']); ?></span></td>
                            <td>
                                <?php if ($row['status'] == 'Completed'): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php elseif ($row['status'] == 'Scheduled'): ?>
                                    <span class="badge badge-info">Scheduled</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         REPORT 4: OUTSTANDING BALANCE REPORT
         ========================================== -->
    <div id="tab-outstanding" class="tab-content">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Accounts Receivable (Outstanding Balances)</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Identifies students with unpaid balances to manage credit terms, target collection notices, and forecast revenue.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Reg ID</th>
                        <th>Student Name</th>
                        <th>Telephone</th>
                        <th>Registered Course</th>
                        <th>Cost</th>
                        <th>Deposit Paid</th>
                        <th>Outstanding Debt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report_balances)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">Great! No outstanding student balances found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report_balances as $row): ?>
                            <tr>
                                <td><code>#REG-<?php echo $row['registration_id']; ?></code></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></td>
                                <td><?php echo htmlspecialchars($row['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td>GH¢<?php echo number_format($row['total_cost'], 2); ?></td>
                                <td>GH¢<?php echo number_format($row['deposit'], 2); ?></td>
                                <td style="color: var(--danger); font-weight: 700;">GH¢<?php echo number_format($row['balance'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         REPORT 5: COMPLETED LESSON REPORT
         ========================================== -->
    <div id="tab-completed" class="tab-content">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Completed Lessons & Audits Report</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Tracks completed training milestones to evaluate student progress and confirm course completion eligibility.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Student Name</th>
                        <th>Instructor</th>
                        <th>Vehicle Used</th>
                        <th>Lesson Class</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report_completed_lessons)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">No completed lessons logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report_completed_lessons as $row): ?>
                            <tr>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($row['lesson_date'])); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></div>
                                </td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></td>
                                <td><?php echo htmlspecialchars($row['instructor_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['vehicle_registration_no']); ?></td>
                                <td><span class="badge badge-success"><?php echo htmlspecialchars($row['lesson_type']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         REPORT 6: VEHICLE ASSIGNMENT / FLEET REPORT
         ========================================== -->
    <div id="tab-vehicles" class="tab-content">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Vehicle Fleet Usage Report</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Evaluates training vehicle utilization metrics and maintenance intervals to manage fleet deployment and fuel costs.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Registration No</th>
                        <th>Transmission & Vehicle Type</th>
                        <th>Current Fleet Status</th>
                        <th>Total Lessons Conducted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_vehicles as $row): ?>
                        <?php 
                        $badge_class = 'badge-success';
                        if ($row['status'] == 'Maintenance') $badge_class = 'badge-warning';
                        if ($row['status'] == 'Out of Service') $badge_class = 'badge-danger';
                        ?>
                        <tr>
                            <td><strong style="font-size: 1rem;"><?php echo htmlspecialchars($row['vehicle_registration_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td style="font-weight: 700;"><?php echo $row['total_lessons']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         REPORT 7: INSTRUCTOR ASSIGNMENT REPORT
         ========================================== -->
    <div id="tab-instructors" class="tab-content">
        <div class="table-container">
            <div class="table-header-bar">
                <div>
                    <h3 class="table-title">Instructor Allocation & Workload Report</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        <strong>Usefulness:</strong> Measures instructor workloads to distribute training loads equitably, plan recruitment, and audit payroll indices.
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Instructor Name</th>
                        <th>License Class Type</th>
                        <th>Contact Number</th>
                        <th>Total Lessons Booked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_instructors as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['instructor_name']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['license_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['telephone']); ?></td>
                            <td style="font-weight: 700;"><?php echo $row['total_lessons']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Tab switching controller
    function switchTab(evt, tabId) {
        // Hide all tab content
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.remove('active');
        });

        // Remove active class from all nav items
        const tabNavItems = document.querySelectorAll('.tab-nav-item');
        tabNavItems.forEach(item => {
            item.classList.remove('active');
        });

        // Show active tab content
        document.getElementById(tabId).classList.add('active');

        // Add active class to clicked nav item
        evt.currentTarget.classList.add('active');
    }
</script>

<?php include 'footer.php'; ?>
