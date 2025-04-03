
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

// Process expense addition if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_expense'])) {
    $expense_date = $_POST['expense_date'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    $sql = "INSERT INTO expenses (user_id, expense_date, name, category, amount, description) VALUES (?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssds", $user_id, $expense_date, $name, $category, $amount, $description);
        
        if ($stmt->execute()) {
            $success_message = "Expense added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Fetch total expenses
$total_expenses = 0;
$sql = "SELECT SUM(amount) as total FROM expenses WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_expenses = $row['total'] ? $row['total'] : 0;
    }
    $stmt->close();
}

// Fetch latest budget
$income = $housing = $transportation = $education = $personal = $savings = 0;
$sql = "SELECT * FROM budgets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $income = $row['income'];
        $housing = $row['housing'];
        $transportation = $row['transportation'];
        $education = $row['education'];
        $personal = $row['personal'];
        $savings = $row['savings'];
    }
    $stmt->close();
}

// Calculate total budget expenses
$total_budget = $housing + $transportation + $education + $personal + $savings;

// Fetch total debt
$total_debt = 0;
$sql = "SELECT SUM(amount_owed) as total FROM debts WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_debt = $row['total'] ? $row['total'] : 0;
    }
    $stmt->close();
}

// Fetch recent expenses
$recent_expenses = [];
$sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY expense_date DESC LIMIT 5";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_expenses[] = $row;
    }
    $stmt->close();
}

// Fetch expense categories for chart
$expense_categories = [];
$expense_amounts = [];
$sql = "SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY category";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expense_categories[] = $row['category'];
        $expense_amounts[] = $row['total'];
    }
    $stmt->close();
}

// Check if expenses exceed budget
$budget_alert = "";
if ($total_expenses > $income) {
    $budget_alert = "Your expenses exceed your income! Consider reviewing your spending habits.";
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FinFlow Harmony Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="dashboard.php" class="navbar-brand">FinFlow Harmony Hub</a>
            <div class="navbar-links">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="budget.php">Budget Tool</a>
                <a href="expenses.php">Expense Tracker</a>
                <a href="debt.php">Debt Manager</a>
                <a href="analytics.php">Analytics</a>
            </div>
            <div class="navbar-auth">
                <span class="user-greeting">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </nav>

        <div class="dashboard-header">
            <h1 class="dashboard-title">Financial Dashboard</h1>
            <div class="dashboard-actions">
                <button class="btn btn-primary" id="addExpenseBtn">Add Expense</button>
                <a href="budget.php" class="btn btn-outline">Update Budget</a>
            </div>
        </div>

        <?php if (!empty($budget_alert)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $budget_alert; ?>
        </div>
        <?php endif; ?>

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
                <h3 class="summary-title">Total Income</h3>
                <p class="summary-value summary-color-income">$<?php echo number_format($income, 2); ?></p>
                <p>Monthly</p>
            </div>
            <div class="dashboard-summary-card">
                <h3 class="summary-title">Total Expenses</h3>
                <p class="summary-value summary-color-expense">$<?php echo number_format($total_expenses, 2); ?></p>
                <p>All time</p>
            </div>
            <div class="dashboard-summary-card">
                <h3 class="summary-title">Budget</h3>
                <p class="summary-value summary-color-budget">$<?php echo number_format($total_budget, 2); ?></p>
                <p>Monthly</p>
            </div>
            <div class="dashboard-summary-card">
                <h3 class="summary-title">Total Debt</h3>
                <p class="summary-value summary-color-debt">$<?php echo number_format($total_debt, 2); ?></p>
                <p>Outstanding</p>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="chart-container">
                <h3 class="chart-title">Expense Breakdown</h3>
                <canvas id="expenseChart"></canvas>
            </div>
        </div>

        <div class="table-container">
            <h3 class="chart-title" style="padding: 15px;">Recent Expenses</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_expenses)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No expenses recorded yet.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_expenses as $expense): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                            <td><?php echo htmlspecialchars($expense['name']); ?></td>
                            <td><?php echo htmlspecialchars($expense['category']); ?></td>
                            <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="text-align: center; padding: 15px;">
                <a href="expenses.php" class="btn btn-outline">View All Expenses</a>
            </div>
        </div>

        <!-- Add Expense Modal -->
        <div id="expenseModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add New Expense</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Expense name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="food">Food</option>
                            <option value="housing">Housing</option>
                            <option value="transportation">Transportation</option>
                            <option value="utilities">Utilities</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="medical">Medical</option>
                            <option value="education">Education</option>
                            <option value="shopping">Shopping</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Optional details"></textarea>
                    </div>
                    <input type="hidden" name="add_expense" value="1">
                    <button type="submit" class="btn btn-primary btn-block">Add Expense</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Additional styles */
        .dashboard-row {
            margin-bottom: 30px;
        }
        
        .user-greeting {
            color: #fff;
            margin-right: 15px;
        }
        
        .btn-block {
            width: 100%;
            display: block;
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
    </style>

    <script>
        // Chart initialization
        const ctx = document.getElementById('expenseChart').getContext('2d');
        
        // Prepare chart data
        const categories = <?php echo json_encode($expense_categories); ?>;
        const amounts = <?php echo json_encode($expense_amounts); ?>;
        
        // Generate random colors for the chart
        const backgroundColors = categories.map(() => 
            `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.6)`
        );
        
        const expenseChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categories,
                datasets: [{
                    data: amounts,
                    backgroundColor: backgroundColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#333',
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });
        
        // Modal functionality
        const modal = document.getElementById("expenseModal");
        const btn = document.getElementById("addExpenseBtn");
        const span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
