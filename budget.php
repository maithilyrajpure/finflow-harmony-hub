
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

// Process budget submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_budget'])) {
    $income = $_POST['income'];
    $housing = $_POST['housing'];
    $transportation = $_POST['transportation'];
    $education = $_POST['education'];
    $personal = $_POST['personal'];
    $savings = $_POST['savings'];

    $sql = "INSERT INTO budgets (user_id, income, housing, transportation, education, personal, savings) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("idddddd", $user_id, $income, $housing, $transportation, $education, $personal, $savings);
        
        if ($stmt->execute()) {
            $success_message = "Budget saved successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
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

// Fetch expense totals by category
$expense_totals = [
    'housing' => 0,
    'transportation' => 0,
    'education' => 0,
    'personal' => 0,
    'food' => 0,
    'entertainment' => 0,
    'utilities' => 0,
    'medical' => 0,
    'shopping' => 0,
    'other' => 0
];

$sql = "SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY category";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category = strtolower($row['category']);
        $expense_totals[$category] = $row['total'];
    }
    $stmt->close();
}

// Group some categories for comparison
$personal_expenses = $expense_totals['food'] + $expense_totals['entertainment'] + $expense_totals['shopping'] + $expense_totals['medical'] + $expense_totals['other'];
$housing_expenses = $expense_totals['housing'] + $expense_totals['utilities'];
$transportation_expenses = $expense_totals['transportation'];
$education_expenses = $expense_totals['education'];

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tool - FinFlow Harmony Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="dashboard.php" class="navbar-brand">FinFlow Harmony Hub</a>
            <div class="navbar-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="budget.php" class="active">Budget Tool</a>
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
            <h1 class="dashboard-title">Personal Budget Tool</h1>
            <p>Plan your finances effectively</p>
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

        <div class="budget-container">
            <div class="dashboard-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Monthly Budget Overview</h3>
                    <canvas id="budgetChart"></canvas>
                    <div class="budget-summary">
                        <h4>Net Income: $<span id="netIncome">0</span></h4>
                    </div>
                </div>

                <div class="budget-form-container card">
                    <h3>Update Your Budget</h3>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="budgetForm">
                        <!-- Income Section -->
                        <div class="budget-section">
                            <h4 class="section-title">Income</h4>
                            <div class="form-group">
                                <label class="form-label">Monthly Income</label>
                                <input type="number" name="income" id="income" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo $income; ?>" required>
                            </div>
                        </div>

                        <!-- Housing Section -->
                        <div class="budget-section">
                            <h4 class="section-title">Housing Expenses</h4>
                            <div class="form-group">
                                <label class="form-label">Total Housing Budget</label>
                                <input type="number" name="housing" id="housing" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo $housing; ?>" required>
                            </div>
                            <?php if ($housing > 0 && $housing_expenses > $housing): ?>
                            <div class="budget-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                Your housing expenses ($<?php echo number_format($housing_expenses, 2); ?>) exceed your budget!
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Transportation Section -->
                        <div class="budget-section">
                            <h4 class="section-title">Transportation Expenses</h4>
                            <div class="form-group">
                                <label class="form-label">Total Transportation Budget</label>
                                <input type="number" name="transportation" id="transportation" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo $transportation; ?>" required>
                            </div>
                            <?php if ($transportation > 0 && $transportation_expenses > $transportation): ?>
                            <div class="budget-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                Your transportation expenses ($<?php echo number_format($transportation_expenses, 2); ?>) exceed your budget!
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Education Section -->
                        <div class="budget-section">
                            <h4 class="section-title">Educational Expenses</h4>
                            <div class="form-group">
                                <label class="form-label">Total Education Budget</label>
                                <input type="number" name="education" id="education" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo $education; ?>" required>
                            </div>
                            <?php if ($education > 0 && $education_expenses > $education): ?>
                            <div class="budget-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                Your education expenses ($<?php echo number_format($education_expenses, 2); ?>) exceed your budget!
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Personal Section -->
                        <div class="budget-section">
                            <h4 class="section-title">Personal & Food Expenses</h4>
                            <div class="form-group">
                                <label class="form-label">Total Personal Budget</label>
                                <input type="number" name="personal" id="personal" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo $personal; ?>" required>
                            </div>
                            <?php if ($personal > 0 && $personal_expenses > $personal): ?>
                            <div class="budget-alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                Your personal expenses ($<?php echo number_format($personal_expenses, 2); ?>) exceed your budget!
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Savings Section -->
                        <div class="budget-section">
                            <h4 class="section-title">Monthly Savings</h4>
                            <div class="form-group">
                                <label class="form-label">Total Savings Budget</label>
                                <input type="number" name="savings" id="savings" class="form-control" placeholder="0.00" step="0.01" min="0" value="<?php echo $savings; ?>" required>
                            </div>
                        </div>

                        <input type="hidden" name="save_budget" value="1">
                        <button type="submit" class="btn btn-primary btn-block">Save Budget</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Additional Budget Tool Styles */
        .budget-container {
            margin-top: 30px;
        }
        
        .budget-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .budget-summary {
            margin-top: 20px;
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .budget-summary h4 {
            font-size: 1.2rem;
            color: #333;
        }
        
        .budget-alert {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .budget-alert i {
            margin-right: 5px;
        }
        
        .btn-block {
            margin-top: 20px;
        }
    </style>

    <script>
        // Budget chart initialization
        const ctx = document.getElementById('budgetChart').getContext('2d');
        
        // Get budget values
        const income = parseFloat(document.getElementById('income').value) || 0;
        const housing = parseFloat(document.getElementById('housing').value) || 0;
        const transportation = parseFloat(document.getElementById('transportation').value) || 0;
        const education = parseFloat(document.getElementById('education').value) || 0;
        const personal = parseFloat(document.getElementById('personal').value) || 0;
        const savings = parseFloat(document.getElementById('savings').value) || 0;
        
        // Calculate net income
        const totalExpenses = housing + transportation + education + personal + savings;
        const netIncome = income - totalExpenses;
        
        // Update net income display
        document.getElementById('netIncome').textContent = netIncome.toFixed(2);
        
        // Create chart
        const budgetChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Housing', 'Transportation', 'Education', 'Personal', 'Savings'],
                datasets: [{
                    data: [housing, transportation, education, personal, savings],
                    backgroundColor: [
                        '#4361ee',
                        '#3a0ca3',
                        '#7209b7',
                        '#f72585',
                        '#4cc9f0'
                    ],
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
        
        // Update chart and net income when form values change
        document.querySelectorAll('#budgetForm input[type="number"]').forEach(input => {
            input.addEventListener('input', updateBudget);
        });
        
        function updateBudget() {
            const income = parseFloat(document.getElementById('income').value) || 0;
            const housing = parseFloat(document.getElementById('housing').value) || 0;
            const transportation = parseFloat(document.getElementById('transportation').value) || 0;
            const education = parseFloat(document.getElementById('education').value) || 0;
            const personal = parseFloat(document.getElementById('personal').value) || 0;
            const savings = parseFloat(document.getElementById('savings').value) || 0;
            
            // Update chart data
            budgetChart.data.datasets[0].data = [housing, transportation, education, personal, savings];
            budgetChart.update();
            
            // Update net income
            const totalExpenses = housing + transportation + education + personal + savings;
            const netIncome = income - totalExpenses;
            document.getElementById('netIncome').textContent = netIncome.toFixed(2);
            
            // Change color based on positive/negative
            if (netIncome < 0) {
                document.getElementById('netIncome').style.color = '#e63946';
            } else {
                document.getElementById('netIncome').style.color = '#2a9d8f';
            }
        }
        
        // Initialize on load
        updateBudget();
    </script>
</body>
</html>
