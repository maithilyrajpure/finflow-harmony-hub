
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "debt_tracker";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    echo "Error creating users table: " . $conn->error;
}

// Create budget table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS budgets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    income DECIMAL(10,2) DEFAULT 0,
    housing DECIMAL(10,2) DEFAULT 0,
    transportation DECIMAL(10,2) DEFAULT 0,
    education DECIMAL(10,2) DEFAULT 0,
    personal DECIMAL(10,2) DEFAULT 0,
    savings DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) !== TRUE) {
    echo "Error creating budgets table: " . $conn->error;
}

// Create debts table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS debts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    debt_type VARCHAR(100) NOT NULL,
    amount_owed DECIMAL(10,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    min_payment DECIMAL(10,2) NOT NULL,
    progress DECIMAL(5,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'In Progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) !== TRUE) {
    echo "Error creating debts table: " . $conn->error;
}

// Ensure expenses table exists with user_id field
$sql = "SHOW TABLES LIKE 'expenses'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Table exists, check if user_id column exists
    $sql = "SHOW COLUMNS FROM expenses LIKE 'user_id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        // Add user_id column
        $sql = "ALTER TABLE expenses ADD COLUMN user_id INT(11) AFTER id";
        if ($conn->query($sql) !== TRUE) {
            echo "Error adding user_id to expenses table: " . $conn->error;
        }
    }
} else {
    // Create expenses table
    $sql = "CREATE TABLE expenses (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11),
        expense_date DATE NOT NULL,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) !== TRUE) {
        echo "Error creating expenses table: " . $conn->error;
    }
}
?>
