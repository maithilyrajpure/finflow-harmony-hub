
<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Include database connection
require_once "database.php";

// Get user ID
$user_id = $_SESSION["id"];

// Process debt actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_debt'])) {
        $debt_type = $_POST['debt_type'];
        $amount_owed = $_POST['amount_owed'];
        $interest_rate = $_POST['interest_rate'];
        $min_payment = $_POST['min_payment'];
        $progress = $_POST['progress'];
        
        $sql = "INSERT INTO debts (user_id, debt_type, amount_owed, interest_rate, min_payment, progress, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'In Progress')";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isdddd", $user_id, $debt_type, $amount_owed, $interest_rate, $min_payment, $progress);
            
            if ($stmt->execute()) {
                $success_message = "Debt added successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
    } elseif (isset($_POST['update_debt'])) {
        $debt_id = $_POST['debt_id'];
        $debt_type = $_POST['debt_type'];
        $amount_owed = $_POST['amount_owed'];
        $interest_rate = $_POST['interest_rate'];
        $min_payment = $_POST['min_payment'];
        $progress = $_POST['progress'];
        $status = ($progress >= 100) ? 'Paid Off' : 'In Progress';
        
        $sql = "UPDATE debts SET debt_type = ?, amount_owed = ?, interest_rate = ?, min_payment = ?, progress = ?, status = ?
                WHERE id = ? AND user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sddddsis", $debt_type, $amount_owed, $interest_rate, $min_payment, $progress, $status, $debt_id, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Debt updated successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
    } elseif (isset($_POST['delete_debt'])) {
        $debt_id = $_POST['debt_id'];
        
        $sql = "DELETE FROM debts WHERE id = ? AND user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $debt_id, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Debt deleted successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}

// Fetch all debts
$debts = [];
$sql = "SELECT * FROM debts WHERE user_id = ? ORDER BY interest_rate DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $debts[] = $row;
    }
    $stmt->close();
}

// Calculate totals
$total_debt = 0;
$total_min_payment = 0;
$avg_interest_rate = 0;

if (!empty($debts)) {
    foreach ($debts as $debt) {
        $total_debt += $debt['amount_owed'];
        $total_min_payment += $debt['min_payment'];
    }
    
    // Calculate weighted average interest rate
    $interest_sum = 0;
    foreach ($debts as $debt) {
        $interest_sum += $debt['interest_rate'] * $debt['amount_owed'];
    }
    $avg_interest_rate = $total_debt > 0 ? $interest_sum / $total_debt : 0;
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debt Manager - FinFlow Harmony Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="dashboard.php" class="navbar-brand">FinFlow Harmony Hub</a>
            <div class="navbar-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="budget.php">Budget Tool</a>
                <a href="expenses.php">Expense Tracker</a>
                <a href="debt.php" class="active">Debt Manager</a>
                <a href="analytics.php">Analytics</a>
            </div>
            <div class="navbar-auth">
                <span class="user-greeting">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </nav>

        <div class="dashboard-header">
            <h1 class="dashboard-title">Debt Repayment Manager</h1>
            <div class="dashboard-actions">
                <button class="btn btn-primary" id="addDebtBtn">Add New Debt</button>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="dashboard-summary-card">
                <h3 class="summary-title">Total Debt</h3>
                <p class="summary-value summary-color-debt">$<?php echo number_format($total_debt, 2); ?></p>
            </div>
            <div class="dashboard-summary-card">
                <h3 class="summary-title">Monthly Payments</h3>
                <p class="summary-value summary-color-expense">$<?php echo number_format($total_min_payment, 2); ?></p>
            </div>
            <div class="dashboard-summary-card">
                <h3 class="summary-title">Avg. Interest Rate</h3>
                <p class="summary-value summary-color-budget"><?php echo number_format($avg_interest_rate, 2); ?>%</p>
            </div>
        </div>

        <div class="card">
            <h3>Debt Strategy Recommendation</h3>
            <div class="strategy-content">
                <?php if (empty($debts)): ?>
                <p>Add your debts to receive a personalized repayment strategy.</p>
                <?php elseif (count($debts) == 1): ?>
                <p>You have only one debt. Focus on making at least the minimum payment each month. Any extra money should go toward this debt.</p>
                <?php else: ?>
                <h4>Avalanche Method (Recommended)</h4>
                <p>Pay minimum payments on all debts, then put extra money toward the debt with the highest interest rate (<?php echo $debts[0]['debt_type']; ?> at <?php echo $debts[0]['interest_rate']; ?>%). This will save you the most money in interest over time.</p>
                
                <h4>Snowball Method</h4>
                <?php 
                    usort($debts, function($a, $b) {
                        return $a['amount_owed'] <=> $b['amount_owed'];
                    });
                ?>
                <p>Pay minimum payments on all debts, then put extra money toward the smallest debt (<?php echo $debts[0]['debt_type']; ?> at $<?php echo number_format($debts[0]['amount_owed'], 2); ?>). This can help build momentum by eliminating debts quickly.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Debt Type</th>
                        <th>Amount Owed</th>
                        <th>Interest Rate</th>
                        <th>Min. Payment</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($debts)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No debts recorded yet.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($debts as $debt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($debt['debt_type']); ?></td>
                            <td>$<?php echo number_format($debt['amount_owed'], 2); ?></td>
                            <td><?php echo number_format($debt['interest_rate'], 2); ?>%</td>
                            <td>$<?php echo number_format($debt['min_payment'], 2); ?></td>
                            <td>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $debt['progress']; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo number_format($debt['progress'], 0); ?>%</span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $debt['status'])); ?>">
                                    <?php echo $debt['status']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-icon btn-edit" data-id="<?php echo $debt['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this debt?');">
                                    <input type="hidden" name="debt_id" value="<?php echo $debt['id']; ?>">
                                    <input type="hidden" name="delete_debt" value="1">
                                    <button type="submit" class="btn-icon btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Debt Modal -->
        <div id="debtModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add New Debt</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label class="form-label">Debt Type</label>
                        <input type="text" name="debt_type" class="form-control" placeholder="Credit Card, Student Loan, etc." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount Owed</label>
                        <input type="number" name="amount_owed" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interest Rate (%)</label>
                        <input type="number" name="interest_rate" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Minimum Monthly Payment</label>
                        <input type="number" name="min_payment" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Progress (%)</label>
                        <input type="number" name="progress" class="form-control" placeholder="0.00" step="0.01" min="0" max="100" value="0" required>
                    </div>
                    <input type="hidden" name="add_debt" value="1">
                    <button type="submit" class="btn btn-primary btn-block">Add Debt</button>
                </form>
            </div>
        </div>

        <!-- Edit Debt Modal -->
        <div id="editDebtModal" class="modal">
            <div class="modal-content">
                <span class="close" id="editModalClose">&times;</span>
                <h2>Edit Debt</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="editDebtForm">
                    <div class="form-group">
                        <label class="form-label">Debt Type</label>
                        <input type="text" name="debt_type" id="edit_debt_type" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount Owed</label>
                        <input type="number" name="amount_owed" id="edit_amount_owed" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interest Rate (%)</label>
                        <input type="number" name="interest_rate" id="edit_interest_rate" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Minimum Monthly Payment</label>
                        <input type="number" name="min_payment" id="edit_min_payment" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Progress (%)</label>
                        <input type="number" name="progress" id="edit_progress" class="form-control" step="0.01" min="0" max="100" required>
                    </div>
                    <input type="hidden" name="debt_id" id="edit_debt_id">
                    <input type="hidden" name="update_debt" value="1">
                    <button type="submit" class="btn btn-primary btn-block">Update Debt</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Additional styles */
        .progress-bar-container {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #4361ee;
            border-radius: 10px;
        }
        
        .progress-text {
            font-size: 0.85rem;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .status-in-progress {
            background-color: #ffc107;
            color: #333;
        }
        
        .status-paid-off {
            background-color: #4caf50;
        }
        
        .btn-icon {
            border: none;
            background: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            margin-right: 5px;
        }
        
        .btn-edit {
            color: #4361ee;
        }
        
        .btn-edit:hover {
            background-color: #e6ecff;
        }
        
        .btn-danger {
            color: #e63946;
        }
        
        .btn-danger:hover {
            background-color: #ffe5e7;
        }
        
        .strategy-content {
            padding: 15px;
        }
        
        .strategy-content h4 {
            margin-top: 15px;
            margin-bottom: 10px;
            color: #333;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: #333;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }
        
        .btn-block {
            width: 100%;
            display: block;
        }
    </style>

    <script>
        // Add Debt Modal
        const modal = document.getElementById("debtModal");
        const btn = document.getElementById("addDebtBtn");
        const span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // Edit Debt Modal
        const editModal = document.getElementById("editDebtModal");
        const editBtns = document.querySelectorAll(".btn-edit");
        const editSpan = document.getElementById("editModalClose");
        
        // Debt data for edit modal
        const debts = <?php echo json_encode($debts); ?>;
        
        editBtns.forEach(btn => {
            btn.addEventListener("click", function() {
                const debtId = this.getAttribute("data-id");
                const debt = debts.find(d => d.id == debtId);
                
                if (debt) {
                    document.getElementById("edit_debt_id").value = debt.id;
                    document.getElementById("edit_debt_type").value = debt.debt_type;
                    document.getElementById("edit_amount_owed").value = debt.amount_owed;
                    document.getElementById("edit_interest_rate").value = debt.interest_rate;
                    document.getElementById("edit_min_payment").value = debt.min_payment;
                    document.getElementById("edit_progress").value = debt.progress;
                    
                    editModal.style.display = "block";
                }
            });
        });
        
        editSpan.onclick = function() {
            editModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
        }
    </script>
</body>
</html>
