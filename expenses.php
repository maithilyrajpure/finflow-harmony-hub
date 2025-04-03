
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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_expense'])) {
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
    } elseif (isset($_POST['delete_expense'])) {
        $expense_id = $_POST['expense_id'];
        
        $sql = "DELETE FROM expenses WHERE id = ? AND user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $expense_id, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Expense deleted successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}

// Fetch all expenses
$expenses = [];
$sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY expense_date DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - FinFlow Harmony Hub</title>
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
                <a href="expenses.php" class="active">Expense Tracker</a>
                <a href="debt.php">Debt Manager</a>
                <a href="analytics.php">Analytics</a>
            </div>
            <div class="navbar-auth">
                <span class="user-greeting">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </nav>

        <div class="dashboard-header">
            <h1 class="dashboard-title">Expense Tracker</h1>
            <div class="dashboard-actions">
                <button class="btn btn-primary" id="addExpenseBtn">Add New Expense</button>
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

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No expenses recorded yet.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                            <td><?php echo htmlspecialchars($expense['name']); ?></td>
                            <td>
                                <span class="category-badge category-<?php echo strtolower($expense['category']); ?>">
                                    <?php echo htmlspecialchars($expense['category']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                    <input type="hidden" name="delete_expense" value="1">
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
        .category-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .category-food { background-color: #4CAF50; }
        .category-housing { background-color: #2196F3; }
        .category-transportation { background-color: #FF9800; }
        .category-utilities { background-color: #9C27B0; }
        .category-entertainment { background-color: #E91E63; }
        .category-medical { background-color: #F44336; }
        .category-education { background-color: #3F51B5; }
        .category-shopping { background-color: #00BCD4; }
        .category-other { background-color: #607D8B; }
        
        .btn-icon {
            border: none;
            background: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .btn-danger {
            color: #e63946;
        }
        
        .btn-danger:hover {
            background-color: #ffe5e7;
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
