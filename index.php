
<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    require_once "database.php";
    
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if username exists, if yes then verify password
                if ($stmt->num_rows == 1) {                    
                    // Bind result variables
                    $stmt->bind_result($id, $username, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to dashboard page
                            header("location: dashboard.php");
                        } else {
                            // Password is not valid
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinFlow Harmony Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">FinFlow Harmony Hub</a>
            <div class="navbar-links">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="navbar-auth">
                <a href="signup.php" class="btn btn-outline">Sign Up</a>
                <button class="btn btn-primary" id="loginBtn">Login</button>
            </div>
        </nav>

        <!-- Login Modal -->
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Login to Your Account</h2>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </div>
                    <p>Don't have an account? <a href="signup.php">Sign up now</a>.</p>
                </form>
            </div>
        </div>

        <!-- Hero Section -->
        <section class="hero">
            <h1 class="hero-title">Take Control of Your Finances</h1>
            <p class="hero-subtitle">FinFlow Harmony Hub helps you track expenses, manage debt, and plan your budget all in one place. Start your journey to financial freedom today.</p>
            <div class="hero-buttons">
                <a href="signup.php" class="btn btn-primary">Get Started</a>
                <a href="#features" class="btn btn-outline">Learn More</a>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Expense Tracking</h3>
                <p class="feature-description">Keep track of where your money goes with our intuitive expense tracker. Categorize expenses and identify spending patterns.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h3 class="feature-title">Budget Planning</h3>
                <p class="feature-description">Create personalized budgets that match your financial goals. Get alerts when you're approaching your limits.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <h3 class="feature-title">Debt Management</h3>
                <p class="feature-description">Track all your debts in one place. Create repayment strategies and watch your progress toward financial freedom.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h3 class="feature-title">Financial Analytics</h3>
                <p class="feature-description">Visualize your financial data with comprehensive charts and reports. Gain insights to make better financial decisions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="feature-title">Secure & Private</h3>
                <p class="feature-description">Your financial data is protected with the highest security standards. Your information is never shared with third parties.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 class="feature-title">Financial Literacy</h3>
                <p class="feature-description">Access resources and tips to improve your financial knowledge and make smarter decisions with your money.</p>
            </div>
        </section>

        <!-- About Section -->
        <section class="card" id="about">
            <h2>About FinFlow Harmony Hub</h2>
            <p>FinFlow Harmony Hub was created with a simple mission: to help people take control of their financial lives. Our platform combines expense tracking, budget planning, and debt management tools to provide a comprehensive solution for personal finance management.</p>
            <p>Whether you're trying to save for a big purchase, pay off debt, or simply understand where your money goes, FinFlow Harmony Hub gives you the tools and insights you need to succeed.</p>
        </section>

        <!-- Contact Section -->
        <section class="card" id="contact">
            <h2>Get in Touch</h2>
            <p>Have questions or feedback? We'd love to hear from you!</p>
            <form>
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" placeholder="Your name">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" placeholder="Your email">
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" rows="4" placeholder="Your message"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </section>

        <!-- Footer -->
        <footer>
            <p>&copy; 2025 FinFlow Harmony Hub. All rights reserved.</p>
        </footer>
    </div>

    <style>
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
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: #333;
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
            margin-top: 20px;
        }

        .is-invalid {
            border-color: #e63946;
        }

        .invalid-feedback {
            color: #e63946;
            font-size: 0.875rem;
            display: block;
            margin-top: 5px;
        }

        footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px 0;
            opacity: 0.8;
        }
    </style>

    <script>
        // Modal functionality
        const modal = document.getElementById("loginModal");
        const btn = document.getElementById("loginBtn");
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
