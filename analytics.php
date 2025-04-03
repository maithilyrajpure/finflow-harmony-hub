
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

// Fetch monthly expenses
$monthly_expenses = [];
$sql = "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total 
        FROM expenses 
        WHERE user_id = ? 
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m') 
        ORDER BY month";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $monthly_expenses[$row['month']] = $row['total'];
    }
    $stmt->close();
}

// Fetch expenses by category
$category_expenses = [];
$sql = "SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? GROUP BY category ORDER BY total DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category_expenses[$row['category']] = $row['total'];
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

// Fetch top expenses
$top_expenses = [];
$sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY amount DESC LIMIT 5";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_expenses[] = $row;
    }
    $stmt->close();
}

// Check for overspent categories
$overspent_categories = [];

// Map expense categories to budget categories
$category_map = [
    'food' => 'personal',
    'housing' => 'housing',
    'transportation' => 'transportation',
    'utilities' => 'housing',
    'entertainment' => 'personal',
    'medical' => 'personal',
    'education' => 'education',
    'shopping' => 'personal',
    'other' => 'personal'
];

// Get current month
$current_month = date('Y-m');

// Get current month expenses by category
$current_month_expenses = [];
$sql = "SELECT category, SUM(amount) as total 
        FROM expenses 
        WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ? 
        GROUP BY category";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("is", $user_id, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_month_expenses[$row['category']] = $row['total'];
    }
    $stmt->close();
}

// Group expenses by budget category
$budget_category_expenses = [
    'housing' => 0,
    'transportation' => 0,
    'education' => 0,
    'personal' => 0
];

foreach ($current_month_expenses as $category => $amount) {
    $budget_category = isset($category_map[$category]) ? $category_map[$category] : 'other';
    $budget_category_expenses[$budget_category] += $amount;
}

// Check for overspending
if ($budget_category_expenses['housing'] > $housing && $housing > 0) {
    $overspent_categories[] = [
        'category' => 'Housing',
        'budget' => $housing,
        'spent' => $budget_category_expenses['housing'],
        'overspent' => $budget_category_expenses['housing'] - $housing
    ];
}

if ($budget_category_expenses['transportation'] > $transportation && $transportation > 0) {
    $overspent_categories[] = [
        'category' => 'Transportation',
        'budget' => $transportation,
        'spent' => $budget_category_expenses['transportation'],
        'overspent' => $budget_category_expenses['transportation'] - $transportation
    ];
}

if ($budget_category_expenses['education'] > $education && $education > 0) {
    $overspent_categories[] = [
        'category' => 'Education',
        'budget' => $education,
        'spent' => $budget_category_expenses['education'],
        'overspent' => $budget_category_expenses['education'] - $education
    ];
}

if ($budget_category_expenses['personal'] > $personal && $personal > 0) {
    $overspent_categories[] = [
        'category' => 'Personal',
        'budget' => $personal,
        'spent' => $budget_category_expenses['personal'],
        'overspent' => $budget_category_expenses['personal'] - $personal
    ];
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - FinFlow Harmony Hub</title>
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
                <a href="budget.php">Budget Tool</a>
                <a href="expenses.php">Expense Tracker</a>
                <a href="debt.php">Debt Manager</a>
                <a href="analytics.php" class="active">Analytics</a>
            </div>
            <div class="navbar-auth">
                <span class="user-greeting">Hello, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </nav>

        <div class="dashboard-header">
            <h1 class="dashboard-title">Financial Analytics</h1>
            <p>Gain insights into your spending patterns and financial health</p>
        </div>

        <!-- Budget Alert Section -->
        <?php if (!empty($overspent_categories)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Budget Alert:</strong> You've exceeded your budget in <?php echo count($overspent_categories); ?> categories this month.
        </div>
        
        <div class="card">
            <h3>Budget Alerts for <?php echo date('F Y'); ?></h3>
            <div class="table-container" style="margin-top: 15px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Budget</th>
                            <th>Spent</th>
                            <th>Overspent</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overspent_categories as $category): ?>
                        <tr>
                            <td><?php echo $category['category']; ?></td>
                            <td>$<?php echo number_format($category['budget'], 2); ?></td>
                            <td>$<?php echo number_format($category['spent'], 2); ?></td>
                            <td>$<?php echo number_format($category['overspent'], 2); ?></td>
                            <td>
                                <span class="status-badge status-over-budget">Over Budget</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Monthly Spending Trends -->
        <div class="chart-container">
            <h3 class="chart-title">Monthly Spending Trends</h3>
            <canvas id="monthlyChart"></canvas>
        </div>

        <!-- Spending by Category -->
        <div class="dashboard-grid">
            <div class="chart-container">
                <h3 class="chart-title">Spending by Category</h3>
                <canvas id="categoryChart"></canvas>
            </div>

            <div class="chart-container">
                <h3 class="chart-title">Budget vs. Actual Spending (This Month)</h3>
                <canvas id="budgetComparisonChart"></canvas>
            </div>
        </div>

        <!-- Top Expenses -->
        <div class="card">
            <h3>Top Expenses</h3>
            <div class="table-container" style="margin-top: 15px;">
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
                        <?php if (empty($top_expenses)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No expenses recorded yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($top_expenses as $expense): ?>
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
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Insights -->
        <div class="card">
            <h3>Financial Insights</h3>
            <div class="insights-container">
                <?php if (empty($monthly_expenses)): ?>
                <p>Start tracking your expenses to receive personalized financial insights.</p>
                <?php else: ?>
                    <?php 
                    $total_spending = array_sum($monthly_expenses);
                    $avg_monthly = count($monthly_expenses) > 0 ? $total_spending / count($monthly_expenses) : 0;
                    
                    // Identify top spending category
                    arsort($category_expenses);
                    $top_category = key($category_expenses);
                    $top_category_amount = reset($category_expenses);
                    $top_category_percent = $total_spending > 0 ? ($top_category_amount / $total_spending) * 100 : 0;
                    
                    // Calculate savings rate
                    $budget_total = $housing + $transportation + $education + $personal;
                    $savings_rate = $income > 0 ? ($savings / $income) * 100 : 0;
                    ?>
                    
                    <div class="insight-card">
                        <div class="insight-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Average Monthly Spending</h4>
                            <p>You spend an average of <strong>$<?php echo number_format($avg_monthly, 2); ?></strong> per month.</p>
                        </div>
                    </div>
                    
                    <div class="insight-card">
                        <div class="insight-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Top Spending Category</h4>
                            <p>Your highest spending category is <strong><?php echo ucfirst($top_category); ?></strong>, which represents <strong><?php echo number_format($top_category_percent, 1); ?>%</strong> of your total spending.</p>
                        </div>
                    </div>
                    
                    <?php if ($savings_rate > 0): ?>
                    <div class="insight-card">
                        <div class="insight-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Savings Rate</h4>
                            <p>Your current savings rate is <strong><?php echo number_format($savings_rate, 1); ?>%</strong> of your income.</p>
                            <?php if ($savings_rate < 20): ?>
                            <p>Financial experts recommend saving at least 20% of your income. Consider increasing your savings if possible.</p>
                            <?php else: ?>
                            <p>Great job! You're saving more than the recommended 20% of your income.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($overspent_categories)): ?>
                    <div class="insight-card">
                        <div class="insight-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Budget Recommendations</h4>
                            <p>You're currently over budget in <?php echo count($overspent_categories); ?> categories. Consider reviewing your spending in these areas or adjusting your budget to be more realistic.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        /* Additional Analytics Styles */
        .insights-container {
            margin-top: 20px;
        }
        
        .insight-card {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .insight-card:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .insight-icon {
            font-size: 2rem;
            color: #4361ee;
            margin-right: 20px;
            min-width: 40px;
            text-align: center;
        }
        
        .insight-content h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
        }
        
        .insight-content p {
            margin: 0 0 10px 0;
            line-height: 1.6;
        }
        
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
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .status-over-budget {
            background-color: #e63946;
        }
    </style>

    <script>
        // Monthly Spending Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        
        // Prepare monthly data
        const monthlyData = <?php echo json_encode($monthly_expenses); ?>;
        const months = Object.keys(monthlyData);
        const spending = Object.values(monthlyData);
        
        // Format month labels (YYYY-MM to MMM YYYY)
        const formattedMonths = months.map(month => {
            const [year, monthNum] = month.split('-');
            const date = new Date(year, monthNum - 1);
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: formattedMonths,
                datasets: [{
                    label: 'Monthly Spending',
                    data: spending,
                    backgroundColor: 'rgba(67, 97, 238, 0.2)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(67, 97, 238, 1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
        
        // Category Spending Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        
        // Prepare category data
        const categoryData = <?php echo json_encode($category_expenses); ?>;
        const categories = Object.keys(categoryData).map(cat => cat.charAt(0).toUpperCase() + cat.slice(1));
        const categorySpending = Object.values(categoryData);
        
        // Generate random colors
        const backgroundColors = categories.map(() => 
            `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.6)`
        );
        
        const categoryChart = new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: categories,
                datasets: [{
                    data: categorySpending,
                    backgroundColor: backgroundColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = ((value * 100) / total).toFixed(1);
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Budget vs Actual Spending Chart
        const budgetCtx = document.getElementById('budgetComparisonChart').getContext('2d');
        
        // Prepare budget vs actual data
        const budgetCategories = ['Housing', 'Transportation', 'Education', 'Personal'];
        const budgetAmounts = [
            <?php echo $housing; ?>, 
            <?php echo $transportation; ?>, 
            <?php echo $education; ?>, 
            <?php echo $personal; ?>
        ];
        const actualAmounts = [
            <?php echo $budget_category_expenses['housing']; ?>,
            <?php echo $budget_category_expenses['transportation']; ?>,
            <?php echo $budget_category_expenses['education']; ?>,
            <?php echo $budget_category_expenses['personal']; ?>
        ];
        
        const budgetChart = new Chart(budgetCtx, {
            type: 'bar',
            data: {
                labels: budgetCategories,
                datasets: [
                    {
                        label: 'Budget',
                        data: budgetAmounts,
                        backgroundColor: 'rgba(67, 97, 238, 0.6)',
                        borderWidth: 0
                    },
                    {
                        label: 'Actual',
                        data: actualAmounts,
                        backgroundColor: 'rgba(231, 76, 60, 0.6)',
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
