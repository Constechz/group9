<?php
// Financial Payments & Receipts Manager
// Almighty Driving School Management System
require_once 'db.php';

$message = '';
$message_type = 'success';

// Handle Add Payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $registration_id = intval($_POST['registration_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $payment_type = $_POST['payment_type'];

    try {
        $pdo->beginTransaction();

        // 1. Fetch active balance
        $stmt_reg = $pdo->prepare("SELECT balance, total_cost, student_id FROM registrations WHERE registration_id = ?");
        $stmt_reg->execute([$registration_id]);
        $reg = $stmt_reg->fetch();

        if (!$reg) {
            throw new Exception("Enrollment record not found!");
        }

        $current_balance = floatval($reg['balance']);
        if ($amount <= 0) {
            throw new Exception("Payment amount must be greater than 0!");
        }
        if ($amount > $current_balance) {
            throw new Exception("Payment amount (GH¢" . number_format($amount, 2) . ") exceeds the outstanding balance (GH¢" . number_format($current_balance, 2) . ")!");
        }

        // 2. Calculate new balance
        $new_balance = $current_balance - $amount;

        // 3. Insert payment
        $stmt_pay = $pdo->prepare("INSERT INTO payments (payment_date, amount, payment_type, balance_after_payment, registration_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_pay->execute([$payment_date, $amount, $payment_type, $new_balance, $registration_id]);
        $payment_id = $pdo->lastInsertId();

        // 4. Update balance in registration table
        $stmt_up = $pdo->prepare("UPDATE registrations SET balance = ? WHERE registration_id = ?");
        $stmt_up->execute([$new_balance, $registration_id]);

        $pdo->commit();
        $message = "Payment logged successfully! You can print the receipt now.";
        
        // Auto-redirect to print receipt
        header("Location: payments.php?receipt=" . $payment_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error logging payment: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Fetch all payments
$payments = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, r.course_name, s.first_name, s.surname, s.telephone 
        FROM payments p 
        JOIN registrations r ON p.registration_id = r.registration_id 
        JOIN students s ON r.student_id = s.student_id 
        ORDER BY p.payment_id DESC
    ");
    $payments = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch active registrations with balance > 0 for dropdown
$registrations_dropdown = [];
try {
    $stmt = $pdo->query("
        SELECT r.registration_id, r.course_name, r.balance, s.first_name, s.surname, s.national_id 
        FROM registrations r 
        JOIN students s ON r.student_id = s.student_id 
        WHERE r.balance > 0 
        ORDER BY s.surname, s.first_name
    ");
    $registrations_dropdown = $stmt->fetchAll();
} catch (Exception $e) {}

// Handle Render Receipt view
if (isset($_GET['receipt'])) {
    $pay_id = intval($_GET['receipt']);
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, r.course_name, r.total_cost, r.registration_date, s.first_name, s.surname, s.telephone, s.national_id, s.residential_address, m.manager_name
            FROM payments p
            JOIN registrations r ON p.registration_id = r.registration_id
            JOIN students s ON r.student_id = s.student_id
            JOIN managers m ON r.manager_id = m.manager_id
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$pay_id]);
        $receipt = $stmt->fetch();
    } catch (Exception $e) {
        $receipt = null;
    }

    if ($receipt) {
        // Output clean Receipt Page that uses print CSS stylesheet rules
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Receipt #PAY-<?php echo $receipt['payment_id']; ?></title>
            <link rel="stylesheet" href="style.css">
            <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
            <style>
                body { background-color: #f1f5f9; padding: 40px 20px; display: block; }
                .receipt-card {
                    max-width: 680px;
                    background-color: white;
                    margin: 0 auto;
                    border-radius: var(--radius-lg);
                    border: 1px solid var(--border-color);
                    box-shadow: var(--shadow-lg);
                    padding: 40px;
                }
                .receipt-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 2px solid var(--border-color);
                    padding-bottom: 24px;
                    margin-bottom: 24px;
                }
                .receipt-title {
                    font-size: 1.5rem;
                    font-weight: 800;
                    color: var(--primary);
                }
                .receipt-meta {
                    text-align: right;
                    font-size: 0.85rem;
                    color: var(--text-secondary);
                }
                .receipt-body h4 {
                    font-size: 1rem;
                    border-bottom: 1px solid var(--border-color);
                    padding-bottom: 8px;
                    margin: 20px 0 12px 0;
                    color: var(--text-primary);
                }
                .detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    font-size: 0.9rem;
                }
                .receipt-footer {
                    margin-top: 40px;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                }
                .sig-line {
                    border-top: 1px solid var(--text-muted);
                    width: 200px;
                    text-align: center;
                    font-size: 0.8rem;
                    color: var(--text-secondary);
                    padding-top: 8px;
                }
            </style>
        </head>
        <body>
            <div class="receipt-card">
                <div class="receipt-header">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <i class="fa-solid fa-car-side" style="color: var(--primary); font-size: 1.5rem;"></i>
                            <strong style="font-size: 1.25rem;">Almighty Driving School</strong>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">P.O. Box 9, Tanoso - Kumasi</div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">Tel: 0244991356 / 0241426886</div>
                    </div>
                    <div class="receipt-meta">
                        <div class="receipt-title">PAYMENT RECEIPT</div>
                        <div style="margin-top: 4px; font-weight: 600;">No: #PAY-<?php echo $receipt['payment_id']; ?></div>
                        <div>Date: <?php echo date('M d, Y', strtotime($receipt['payment_date'])); ?></div>
                    </div>
                </div>

                <div class="receipt-body">
                    <h4>Student Information</h4>
                    <div class="detail-row">
                        <span><strong>Full Name:</strong></span>
                        <span><?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['surname']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><strong>National ID (Ghana Card):</strong></span>
                        <span><?php echo htmlspecialchars($receipt['national_id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><strong>Telephone:</strong></span>
                        <span><?php echo htmlspecialchars($receipt['telephone']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><strong>Address:</strong></span>
                        <span><?php echo htmlspecialchars($receipt['residential_address']); ?></span>
                    </div>

                    <h4>Payment Details</h4>
                    <div class="detail-row">
                        <span><strong>Registered Course:</strong></span>
                        <span><?php echo htmlspecialchars($receipt['course_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><strong>Total Course Cost:</strong></span>
                        <span>GH¢<?php echo number_format($receipt['total_cost'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><strong>Payment Type:</strong></span>
                        <span><?php echo htmlspecialchars($receipt['payment_type']); ?></span>
                    </div>
                    <div class="detail-row" style="font-size: 1.15rem; font-weight: 800; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 8px;">
                        <span>Amount Paid:</span>
                        <span style="color: var(--success);">GH¢<?php echo number_format($receipt['amount'], 2); ?></span>
                    </div>
                    <div class="detail-row" style="font-size: 1.05rem; font-weight: 700; color: var(--text-secondary);">
                        <span>Remaining Balance:</span>
                        <span>GH¢<?php echo number_format($receipt['balance_after_payment'], 2); ?></span>
                    </div>
                </div>

                <div class="receipt-footer">
                    <div>
                        <button onclick="window.print()" class="btn btn-primary no-print">
                            <i class="fa-solid fa-print"></i> Print Receipt
                        </button>
                        <a href="payments.php" class="btn btn-secondary no-print" style="margin-left: 8px;">Back to Payments</a>
                    </div>
                    <div>
                        <div style="height: 50px;"></div>
                        <div class="sig-line">Manager's Signature</div>
                        <div style="text-align: center; font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">(<?php echo htmlspecialchars($receipt['manager_name']); ?>)</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        echo "<div class='alert alert-danger'>Receipt not found.</div>";
    }
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

<!-- Payments Grid -->
<div class="table-container">
    <div class="table-header-bar">
        <h3 class="table-title">Billing Transactions</h3>
        <button onclick="openModal('paymentModal')" class="btn btn-primary">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Log Payment</span>
        </button>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>Receipt ID</th>
                <th>Student</th>
                <th>Course Name</th>
                <th>Amount Paid</th>
                <th>Balance After</th>
                <th>Date Paid</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">No payment records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td><code>#PAY-<?php echo $pay['payment_id']; ?></code></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($pay['first_name'] . ' ' . $pay['surname']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($pay['telephone']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($pay['course_name']); ?></td>
                        <td style="font-weight: 700; color: var(--success);">GH¢<?php echo number_format($pay['amount'], 2); ?></td>
                        <td style="font-weight: 600; color: var(--text-secondary);">GH¢<?php echo number_format($pay['balance_after_payment'], 2); ?></td>
                        <td>
                            <div><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($pay['payment_type']); ?></div>
                        </td>
                        <td>
                            <a href="payments.php?receipt=<?php echo $pay['payment_id']; ?>" class="btn btn-secondary" style="padding: 6px 10px; font-size: 0.8rem;" title="View Receipt">
                                <i class="fa-solid fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ==========================================
     LOG PAYMENT MODAL
     ========================================== -->
<div class="modal" id="paymentModal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h4 class="modal-title">Record Payment</h4>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST" action="payments.php">
            <input type="hidden" name="action" value="save">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Course Enrollment *</label>
                    <select name="registration_id" id="reg_select" class="form-control" onchange="updateMaxPayment()" required>
                        <option value="">-- Select Active Course Account --</option>
                        <?php foreach ($registrations_dropdown as $reg): ?>
                            <option value="<?php echo $reg['registration_id']; ?>" data-balance="<?php echo $reg['balance']; ?>" <?php echo (isset($_GET['reg_id']) && $_GET['reg_id'] == $reg['registration_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($reg['surname'] . ', ' . $reg['first_name'] . ' - ' . $reg['course_name'] . ' (Bal: GH¢' . number_format($reg['balance'], 2) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Date *</label>
                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Amount (GH¢) *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="pay_amount" class="form-control" placeholder="0.00" required>
                        <div id="max_info" style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_type" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Receive Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateMaxPayment() {
        const select = document.getElementById('reg_select');
        const amountInput = document.getElementById('pay_amount');
        const maxInfo = document.getElementById('max_info');
        
        if (select.selectedIndex > 0) {
            const balance = parseFloat(select.options[select.selectedIndex].getAttribute('data-balance'));
            amountInput.max = balance;
            maxInfo.innerHTML = 'Outstanding: <strong>GH¢' + balance.toFixed(2) + '</strong>';
        } else {
            amountInput.removeAttribute('max');
            maxInfo.innerHTML = '';
        }
    }

    // Run automatically on load if registration is pre-selected
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('reg_select');
        if (select && select.value !== '') {
            updateMaxPayment();
            openModal('paymentModal');
        }
    });
</script>

<?php include 'footer.php'; ?>
