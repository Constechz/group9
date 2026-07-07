<?php
// Student Registry & CRUD Management
// Almighty Driving School Management System
require_once 'db.php';

// Handle Details AJAX Endpoint at the very top (before any HTML/headers are sent)
if (isset($_GET['get_details_json'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['manager_id'])) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
    $student_id = intval($_GET['get_details_json']);
    $registrations = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM registrations WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $registrations = $stmt->fetchAll();
    } catch (Exception $e) {}
    
    header('Content-Type: application/json');
    echo json_encode(['registrations' => $registrations]);
    exit;
}


$message = '';
$message_type = 'success';

// Handle Add/Edit Student form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'save') {
        $student_id = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
        $surname = trim($_POST['surname']);
        $first_name = trim($_POST['first_name']);
        $middle_name = !empty($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
        $date_of_birth = $_POST['date_of_birth'];
        $place_of_birth = trim($_POST['place_of_birth']);
        $residential_address = trim($_POST['residential_address']);
        $telephone = trim($_POST['telephone']);
        $email = trim($_POST['email']);
        $national_id = trim($_POST['national_id']);
        $occupation = trim($_POST['occupation']);
        $permit_number = !empty($_POST['permit_number']) ? trim($_POST['permit_number']) : null;
        $permit_expiry_date = !empty($_POST['permit_expiry_date']) ? $_POST['permit_expiry_date'] : null;

        try {
            if ($student_id) {
                // UPDATE Student
                $stmt = $pdo->prepare("UPDATE students SET surname=?, first_name=?, middle_name=?, date_of_birth=?, place_of_birth=?, residential_address=?, telephone=?, email=?, national_id=?, occupation=?, permit_number=?, permit_expiry_date=? WHERE student_id=?");
                $stmt->execute([$surname, $first_name, $middle_name, $date_of_birth, $place_of_birth, $residential_address, $telephone, $email, $national_id, $occupation, $permit_number, $permit_expiry_date, $student_id]);
                $message = "Student profile updated successfully!";
            } else {
                // INSERT New Student
                $stmt = $pdo->prepare("INSERT INTO students (surname, first_name, middle_name, date_of_birth, place_of_birth, residential_address, telephone, email, national_id, occupation, permit_number, permit_expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$surname, $first_name, $middle_name, $date_of_birth, $place_of_birth, $residential_address, $telephone, $email, $national_id, $occupation, $permit_number, $permit_expiry_date]);
                $message = "New student registered successfully!";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle Delete Student
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id=?");
        $stmt->execute([$del_id]);
        $message = "Student deleted successfully!";
    } catch (Exception $e) {
        $message = "Error deleting student (possibly linked to existing registration/lesson records): " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Search filter
$search_term = '';
$query = "SELECT * FROM students";
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $query .= " WHERE surname LIKE ? OR first_name LIKE ? OR national_id LIKE ? OR email LIKE ? OR telephone LIKE ?";
    $like_val = "%$search_term%";
    $params = [$like_val, $like_val, $like_val, $like_val, $like_val];
}
$query .= " ORDER BY student_id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    $students = [];
}

include 'header.php';
?>

<!-- Message Notification -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fa-solid <?php echo ($message_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<!-- Main Section -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-title">Registered Students</h3>
        <div style="display: flex; gap: 16px; align-items: center;">
            <!-- Search Form -->
            <form method="GET" action="students.php" class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search students..." value="<?php echo htmlspecialchars($search_term); ?>">
            </form>
            <button onclick="openAddModal()" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add Student</span>
            </button>
        </div>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Contact Info</th>
                <th>National ID</th>
                <th>Permit Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">No student records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <?php 
                    // Calculate permit status
                    $permit_status = 'No Permit';
                    $badge_class = 'badge-danger';
                    if (!empty($student['permit_number'])) {
                        if (!empty($student['permit_expiry_date']) && strtotime($student['permit_expiry_date']) < time()) {
                            $permit_status = 'Expired Permit';
                            $badge_class = 'badge-warning';
                        } else {
                            $permit_status = 'Valid Permit';
                            $badge_class = 'badge-success';
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo $student['student_id']; ?></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($student['occupation']); ?></div>
                        </td>
                        <td>
                            <div><i class="fa-solid fa-phone" style="font-size: 0.75rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($student['telephone']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);"><i class="fa-solid fa-envelope" style="font-size: 0.75rem; color: var(--text-muted);"></i> <?php echo htmlspecialchars($student['email']); ?></div>
                        </td>
                        <td><code><?php echo htmlspecialchars($student['national_id']); ?></code></td>
                        <td>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo $permit_status; ?>
                            </span>
                            <?php if ($student['permit_number']): ?>
                                <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 4px;">No: <?php echo htmlspecialchars($student['permit_number']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="viewStudentDetails(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem;" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--primary);" title="Edit Profile">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="students.php?delete=<?php echo $student['student_id']; ?>" onclick="return confirm('Are you sure you want to delete this student profile? This will delete all course registrations and payments linked to this student.')" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; color: var(--danger);" title="Delete Student">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ==========================================
     ADD / EDIT STUDENT MODAL
     ========================================== -->
<div class="modal" id="studentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="modalTitle">Register New Student</h4>
            <button class="modal-close" onclick="closeModal('studentModal')">&times;</button>
        </div>
        <form method="POST" action="students.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="student_id" id="student_id">
            
            <div class="modal-body">
                <!-- Row 1 Name Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Surname *</label>
                        <input type="text" name="surname" id="surname" class="form-control" required>
                    </div>
                </div>
                
                <!-- Row 2 Birth Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date of Birth *</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" name="place_of_birth" id="place_of_birth" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" id="occupation" class="form-control">
                    </div>
                </div>

                <!-- Row 3 Contact details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Telephone *</label>
                        <input type="text" name="telephone" id="telephone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">National ID (Ghana Card) *</label>
                        <input type="text" name="national_id" id="national_id" class="form-control" placeholder="GHA-XXXXXXXXX-X" required>
                    </div>
                </div>

                <!-- Row 4 Address -->
                <div class="form-group">
                    <label class="form-label">Residential Address *</label>
                    <input type="text" name="residential_address" id="residential_address" class="form-control" required>
                </div>

                <!-- Row 5 Permit details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Learner's Permit Number</label>
                        <input type="text" name="permit_number" id="permit_number" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Permit Expiry Date</label>
                        <input type="date" name="permit_expiry_date" id="permit_expiry_date" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('studentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Student</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     STUDENT PROFILE DETAIL MODAL
     ========================================== -->
<div class="modal" id="detailsModal">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h4 class="modal-title">Student Academic Profile</h4>
            <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsModalBody">
            <!-- Dynamic Content loaded via JS -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detailsModal')">Close</button>
        </div>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Register New Student';
        document.getElementById('student_id').value = '';
        document.getElementById('first_name').value = '';
        document.getElementById('middle_name').value = '';
        document.getElementById('surname').value = '';
        document.getElementById('date_of_birth').value = '';
        document.getElementById('place_of_birth').value = '';
        document.getElementById('occupation').value = '';
        document.getElementById('telephone').value = '';
        document.getElementById('email').value = '';
        document.getElementById('national_id').value = '';
        document.getElementById('residential_address').value = '';
        document.getElementById('permit_number').value = '';
        document.getElementById('permit_expiry_date').value = '';
        openModal('studentModal');
    }

    function openEditModal(student) {
        document.getElementById('modalTitle').innerText = 'Edit Student Profile';
        document.getElementById('student_id').value = student.student_id;
        document.getElementById('first_name').value = student.first_name;
        document.getElementById('middle_name').value = student.middle_name || '';
        document.getElementById('surname').value = student.surname;
        document.getElementById('date_of_birth').value = student.date_of_birth;
        document.getElementById('place_of_birth').value = student.place_of_birth || '';
        document.getElementById('occupation').value = student.occupation || '';
        document.getElementById('telephone').value = student.telephone;
        document.getElementById('email').value = student.email;
        document.getElementById('national_id').value = student.national_id;
        document.getElementById('residential_address').value = student.residential_address;
        document.getElementById('permit_number').value = student.permit_number || '';
        document.getElementById('permit_expiry_date').value = student.permit_expiry_date || '';
        openModal('studentModal');
    }

    function viewStudentDetails(student) {
        // Fetch detailed registrations and payments asynchronously using a small PHP subquery
        fetch('students.php?get_details_json=' + student.student_id)
            .then(res => res.json())
            .then(data => {
                let html = `
                    <div style="display: flex; gap: 24px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 24px;">
                        <div class="user-avatar" style="width: 72px; height: 72px; font-size: 2rem; border-radius: 12px; background-color: var(--primary-light); color: var(--primary);">
                            ${student.first_name[0]}${student.surname[0]}
                        </div>
                        <div style="flex-grow: 1;">
                            <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">${student.first_name} ${student.middle_name ? student.middle_name + ' ' : ''}${student.surname}</h3>
                            <p style="color: var(--text-secondary); margin-top: 4px;"><i class="fa-solid fa-address-card" style="margin-right: 6px;"></i>Ghana Card: ${student.national_id}</p>
                            <p style="color: var(--text-secondary); margin-top: 2px;"><i class="fa-solid fa-briefcase" style="margin-right: 6px;"></i>Occupation: ${student.occupation || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        <div>
                            <h4 style="font-weight: 700; color: var(--text-primary); margin-bottom: 12px;"><i class="fa-solid fa-circle-info" style="color: var(--primary); margin-right: 6px;"></i>General Details</h4>
                            <p style="font-size: 0.9rem; margin-bottom: 8px;"><strong>Date of Birth:</strong> ${new Date(student.date_of_birth).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}</p>
                            <p style="font-size: 0.9rem; margin-bottom: 8px;"><strong>Place of Birth:</strong> ${student.place_of_birth || 'N/A'}</p>
                            <p style="font-size: 0.9rem; margin-bottom: 8px;"><strong>Residential Address:</strong> ${student.residential_address}</p>
                            <p style="font-size: 0.9rem; margin-bottom: 8px;"><strong>Telephone:</strong> ${student.telephone}</p>
                            <p style="font-size: 0.9rem;"><strong>Email:</strong> ${student.email}</p>
                        </div>
                        
                        <div>
                            <h4 style="font-weight: 700; color: var(--text-primary); margin-bottom: 12px;"><i class="fa-solid fa-id-card" style="color: var(--accent); margin-right: 6px;"></i>Driving Permit Status</h4>
                            <p style="font-size: 0.9rem; margin-bottom: 8px;"><strong>Permit Number:</strong> ${student.permit_number || 'None'}</p>
                            <p style="font-size: 0.9rem; margin-bottom: 8px;"><strong>Expiry Date:</strong> ${student.permit_expiry_date ? new Date(student.permit_expiry_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A'}</p>
                            <div style="margin-top: 12px;">
                                ${student.permit_number 
                                    ? (new Date(student.permit_expiry_date) < new Date() 
                                        ? '<span class="badge badge-warning">Expired Permit</span>' 
                                        : '<span class="badge badge-success">Valid Permit</span>')
                                    : '<span class="badge badge-danger">No Permit Registered</span>'
                                }
                            </div>
                        </div>
                    </div>
                    
                    <h4 style="font-weight: 700; color: var(--text-primary); margin-bottom: 12px; border-top: 1px solid var(--border-color); padding-top: 24px;">Course Registrations & Account Balances</h4>
                `;
                
                if (data.registrations.length === 0) {
                    html += `<p style="color: var(--text-secondary); font-size: 0.9rem; font-style: italic;">No course registrations logged for this student yet.</p>`;
                } else {
                    html += `
                        <div class="table-container" style="box-shadow: none; border-radius: var(--radius-md); margin-bottom: 0;">
                            <table class="custom-table" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Reg Date</th>
                                        <th>Course Name</th>
                                        <th>Cost</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Enrollment</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    data.registrations.forEach(r => {
                        let paid = parseFloat(r.total_cost) - parseFloat(r.balance);
                        html += `
                            <tr>
                                <td>${r.registration_date}</td>
                                <td style="font-weight: 600;">${r.course_name}</td>
                                <td>GH¢${parseFloat(r.total_cost).toFixed(2)}</td>
                                <td>GH¢${paid.toFixed(2)}</td>
                                <td style="color: ${r.balance > 0 ? 'var(--warning)' : 'var(--success)'}; font-weight: 700;">GH¢${parseFloat(r.balance).toFixed(2)}</td>
                                <td>
                                    ${r.commencement_date ? `<span class="badge badge-success">Active</span>` : `<span class="badge badge-info">Pending</span>`}
                                </td>
                            </tr>
                        `;
                    });
                    html += `</tbody></table></div>`;
                }
                
                document.getElementById('detailsModalBody').innerHTML = html;
                openModal('detailsModal');
            });
    }
</script>

<?php


// Open add modal if action=new in URL parameter
if (isset($_GET['action']) && $_GET['action'] == 'new') {
    echo "<script>document.addEventListener('DOMContentLoaded', openAddModal);</script>";
}

include 'footer.php';
?>
