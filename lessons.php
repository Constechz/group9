<?php
// Lesson Scheduler & Conflict Manager
// Almighty Driving School Management System
require_once 'db.php';

$message = '';
$message_type = 'success';

// Handle Add/Edit Lesson
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $lesson_id = !empty($_POST['lesson_id']) ? intval($_POST['lesson_id']) : null;
    $registration_id = intval($_POST['registration_id']);
    $instructor_id = intval($_POST['instructor_id']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $lesson_type = trim($_POST['lesson_type']);
    $lesson_date = $_POST['lesson_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];

    try {
        if ($start_time >= $end_time) {
            throw new Exception("Start time must be earlier than end time!");
        }

        // --- CONFLICT CHECK LOGIC ---
        
        // Define base query to check overlapping times
        // A conflict occurs if the search time block overlaps with an existing lesson (except when modifying the SAME lesson)
        $overlap_cond = "
            lesson_date = ? 
            AND status != 'Cancelled' 
            AND (
                (start_time <= ? AND end_time > ?) OR 
                (start_time < ? AND end_time >= ?) OR 
                (? <= start_time AND ? >= end_time)
            )
        ";
        
        $params_check = [$lesson_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time];
        
        if ($lesson_id) {
            $overlap_cond .= " AND lesson_id != ?";
            $params_check[] = $lesson_id;
        }

        // 1. Check Instructor Scheduling Conflict
        $stmt_inst = $pdo->prepare("SELECT COUNT(*) FROM lesson WHERE instructor_id = ? AND " . $overlap_cond);
        $inst_params = array_merge([$instructor_id], $params_check);
        $stmt_inst->execute($inst_params);
        if ($stmt_inst->fetchColumn() > 0) {
            // Fetch name for error
            $stmt_name = $pdo->prepare("SELECT instructor_name FROM instructor WHERE instructor_id = ?");
            $stmt_name->execute([$instructor_id]);
            $inst_name = $stmt_name->fetchColumn();
            throw new Exception("Conflict! Instructor <strong>$inst_name</strong> is already assigned to a lesson during this time frame.");
        }

        // 2. Check Vehicle Scheduling Conflict
        $stmt_veh = $pdo->prepare("SELECT COUNT(*) FROM lesson WHERE vehicle_id = ? AND " . $overlap_cond);
        $veh_params = array_merge([$vehicle_id], $params_check);
        $stmt_veh->execute($veh_params);
        if ($stmt_veh->fetchColumn() > 0) {
            $stmt_no = $pdo->prepare("SELECT vehicle_registration_no FROM vehicle WHERE vehicle_id = ?");
            $stmt_no->execute([$vehicle_id]);
            $reg_no = $stmt_no->fetchColumn();
            throw new Exception("Conflict! Vehicle <strong>$reg_no</strong> is already booked for another lesson during this time frame.");
        }

        // 3. Check Student Scheduling Conflict
        $stmt_stud = $pdo->prepare("SELECT COUNT(*) FROM lesson WHERE registration_id = ? AND " . $overlap_cond);
        $stud_params = array_merge([$registration_id], $params_check);
        $stmt_stud->execute($stud_params);
        if ($stmt_stud->fetchColumn() > 0) {
            throw new Exception("Conflict! This student already has another lesson scheduled during this time frame.");
        }

        // If no conflicts, proceed with save
        if ($lesson_id) {
            $stmt_save = $pdo->prepare("UPDATE lesson SET registration_id=?, instructor_id=?, vehicle_id=?, lesson_type=?, lesson_date=?, start_time=?, end_time=?, status=? WHERE lesson_id=?");
            $stmt_save->execute([$registration_id, $instructor_id, $vehicle_id, $lesson_type, $lesson_date, $start_time, $end_time, $status, $lesson_id]);
            $message = "Lesson details updated successfully!";
        } else {
            $stmt_save = $pdo->prepare("INSERT INTO lesson (registration_id, instructor_id, vehicle_id, lesson_type, lesson_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_save->execute([$registration_id, $instructor_id, $vehicle_id, $lesson_type, $lesson_date, $start_time, $end_time, $status]);
            $message = "New driving lesson scheduled successfully!";
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Cancel Lesson Quick action
if (isset($_GET['cancel'])) {
    $cancel_id = intval($_GET['cancel']);
    try {
        $stmt = $pdo->prepare("UPDATE lesson SET status='Cancelled' WHERE lesson_id=?");
        $stmt->execute([$cancel_id]);
        $message = "Lesson cancelled successfully.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Handle Mark Completed Quick action
if (isset($_GET['complete'])) {
    $complete_id = intval($_GET['complete']);
    try {
        $stmt = $pdo->prepare("UPDATE lesson SET status='Completed' WHERE lesson_id=?");
        $stmt->execute([$complete_id]);
        $message = "Lesson marked as Completed.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all lessons
$lessons = [];
try {
    $stmt = $pdo->query("
        SELECT l.*, s.first_name, s.surname, s.telephone, i.instructor_name, v.vehicle_registration_no, v.vehicle_type 
        FROM lesson l
        JOIN registration r ON l.registration_id = r.registration_id
        JOIN student s ON r.student_id = s.student_id
        JOIN instructor i ON l.instructor_id = i.instructor_id
        JOIN vehicle v ON l.vehicle_id = v.vehicle_id
        ORDER BY l.lesson_date DESC, l.start_time DESC
    ");
    $lessons = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch active student registrations for dropdown
$registrations_dropdown = [];
try {
    $stmt = $pdo->query("
        SELECT r.registration_id, r.course_name, s.first_name, s.surname, s.national_id 
        FROM registration r 
        JOIN student s ON r.student_id = s.student_id
        ORDER BY s.surname, s.first_name
    ");
    $registrations_dropdown = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch active instructors
$instructors_dropdown = [];
try {
    $stmt = $pdo->query("SELECT instructor_id, instructor_name, license_type FROM instructor ORDER BY instructor_name");
    $instructors_dropdown = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch vehicles (only Active vehicles should be used for scheduling)
$vehicles_dropdown = [];
try {
    $stmt = $pdo->query("SELECT vehicle_id, vehicle_registration_no, vehicle_type FROM vehicle WHERE status='Active' ORDER BY vehicle_registration_no");
    $vehicles_dropdown = $stmt->fetchAll();
} catch (Exception $e) {}

include 'header.php';
?>

<!-- Message Notification -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fa-solid <?php echo ($message_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo $message; ?></span>
    </div>
<?php endif; ?>

<!-- Lessons Grid -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-title">Scheduled Lessons Log</h3>
        <button onclick="openAddModal()" class="btn btn-primary">
            <i class="fa-solid fa-calendar-plus"></i>
            <span>Schedule Lesson</span>
        </button>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Instructor & Vehicle</th>
                <th>Time Block</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lessons)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">No driving lessons scheduled yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($lessons as $l): ?>
                    <tr>
                        <td><code>#LES-<?php echo $l['lesson_id']; ?></code></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($l['first_name'] . ' ' . $l['surname']); ?></div>
                            <div style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo htmlspecialchars($l['telephone']); ?></div>
                        </td>
                        <td>
                            <div><i class="fa-solid fa-user-tie" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($l['instructor_name']); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-car" style="font-size: 0.8rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($l['vehicle_registration_no'] . ' (' . $l['vehicle_type'] . ')'); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($l['lesson_date'])); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo date('h:i A', strtotime($l['start_time'])) . ' - ' . date('h:i A', strtotime($l['end_time'])); ?></div>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($l['lesson_type']); ?></span>
                        </td>
                        <td>
                            <?php if ($l['status'] == 'Completed'): ?>
                                <span class="badge badge-success">Completed</span>
                            <?php elseif ($l['status'] == 'Scheduled'): ?>
                                <span class="badge badge-info">Scheduled</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <?php if ($l['status'] == 'Scheduled'): ?>
                                    <a href="lessons.php?complete=<?php echo $l['lesson_id']; ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--success);" title="Mark Complete">
                                        <i class="fa-solid fa-square-check"></i>
                                    </a>
                                    <a href="lessons.php?cancel=<?php echo $l['lesson_id']; ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--danger);" title="Cancel Lesson">
                                        <i class="fa-solid fa-calendar-xmark"></i>
                                    </a>
                                <?php endif; ?>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($l)); ?>)" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem;" title="Reschedule">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ==========================================
     SCHEDULE LESSON MODAL
     ========================================== -->
<div class="modal" id="lessonModal">
    <div class="modal-content" style="max-width: 550px;">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">Schedule Driving Lesson</h4>
            <button class="modal-close" onclick="closeModal('lessonModal')">&times;</button>
        </div>
        <form method="POST" action="lessons.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="lesson_id" id="lesson_id">
            
            <div class="modal-body">
                <!-- Row 1: Student registration -->
                <div class="form-group">
                    <label class="form-label">Student (Course Enrollment) *</label>
                    <select name="registration_id" id="registration_id" class="form-control" required>
                        <option value="">-- Select Active Student Course --</option>
                        <?php foreach ($registrations_dropdown as $reg): ?>
                            <option value="<?php echo $reg['registration_id']; ?>">
                                <?php echo htmlspecialchars($reg['surname'] . ', ' . $reg['first_name'] . ' - ' . $reg['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Row 2: Instructor and Vehicle -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assigned Instructor *</label>
                        <select name="instructor_id" id="instructor_id" class="form-control" required>
                            <option value="">-- Select Instructor --</option>
                            <?php foreach ($instructors_dropdown as $inst): ?>
                                <option value="<?php echo $inst['instructor_id']; ?>">
                                    <?php echo htmlspecialchars($inst['instructor_name'] . ' (' . $inst['license_type'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fleet Vehicle *</label>
                        <select name="vehicle_id" id="vehicle_id" class="form-control" required>
                            <option value="">-- Select Vehicle --</option>
                            <?php foreach ($vehicles_dropdown as $veh): ?>
                                <option value="<?php echo $veh['vehicle_id']; ?>">
                                    <?php echo htmlspecialchars($veh['vehicle_registration_no'] . ' - ' . $veh['vehicle_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Dates & Times -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Lesson Date *</label>
                        <input type="date" name="lesson_date" id="lesson_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Lesson Type *</label>
                        <select name="lesson_type" id="lesson_type" class="form-control" required>
                            <option value="Practical">Practical Road Lesson</option>
                            <option value="Theory">Classroom Theory</option>
                            <option value="Assessment">Official Pre-Test Assessment</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Time *</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Time *</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required>
                    </div>
                </div>

                <!-- Row 4: Status -->
                <div class="form-group">
                    <label class="form-label">Lesson Status *</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('lessonModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule / Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Schedule Driving Lesson';
        document.getElementById('lesson_id').value = '';
        document.getElementById('registration_id').value = '';
        document.getElementById('instructor_id').value = '';
        document.getElementById('vehicle_id').value = '';
        document.getElementById('lesson_date').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('lesson_type').value = 'Practical';
        document.getElementById('start_time').value = '08:00';
        document.getElementById('end_time').value = '10:00';
        document.getElementById('status').value = 'Scheduled';
        openModal('lessonModal');
    }

    function openEditModal(lesson) {
        document.getElementById('modalTitle').innerText = 'Reschedule Driving Lesson';
        document.getElementById('lesson_id').value = lesson.lesson_id;
        document.getElementById('registration_id').value = lesson.registration_id;
        document.getElementById('instructor_id').value = lesson.instructor_id;
        document.getElementById('vehicle_id').value = lesson.vehicle_id;
        document.getElementById('lesson_date').value = lesson.lesson_date;
        document.getElementById('lesson_type').value = lesson.lesson_type;
        // Strip seconds if present in mysql TIME (e.g. 08:00:00 -> 08:00)
        document.getElementById('start_time').value = lesson.start_time.substring(0, 5);
        document.getElementById('end_time').value = lesson.end_time.substring(0, 5);
        document.getElementById('status').value = lesson.status;
        openModal('lessonModal');
    }
</script>

<?php
// Open schedule modal automatically if action=new parameter is set in URL
if (isset($_GET['action']) && $_GET['action'] == 'new') {
    echo "<script>document.addEventListener('DOMContentLoaded', openAddModal);</script>";
}
include 'footer.php';
?>
