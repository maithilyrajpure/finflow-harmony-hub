
# FinTrack - Financial Management Application

FinTrack is a comprehensive financial management application built with PHP, JavaScript, HTML, and CSS. It includes features for expense tracking, budget planning, debt management, and financial reporting.

## Features

- **User Authentication**: Secure login and registration system
- **Dashboard**: Overview of your financial situation
- **Expense Tracker**: Log and categorize expenses
- **Budget Tool**: Create detailed budgets across multiple categories
- **Debt Repayment Tool**: Track debts and get repayment strategies
- **Financial Reports**: Visualize your financial data
- **Budget Alerts**: Get notified when you exceed your budget

## Setup Instructions

### Database Setup

1. Create a MySQL database named `debt_tracker`
2. Navigate to the project directory
3. Run the database setup script: `php setup_database.php`
4. This will create the necessary tables for the application

### Configuration

You may need to modify the database connection parameters in these files:
- `login_process.php`
- `register_process.php`
- `setup_database.php`

The default settings are:
- Server: localhost
- Username: root
- Password: (empty)
- Database: debt_tracker

## Usage

1. Start your local web server (like XAMPP, WAMP, or using PHP's built-in server)
2. Navigate to the application in your web browser
3. Register a new account
4. Log in to access the dashboard
5. Use the navigation menu to access different features

## File Structure

- `index.html` - Dashboard/home page
- `login.html` & `register.html` - Authentication pages
- `expenses.html` - Expense tracking
- `budget.html` - Budget planning
- `debt.html` - Debt management
- `reports.html` - Financial reporting
- `*.js` files - JavaScript functionality
- `*.php` files - Server-side processing
- `styles.css` - Main stylesheet

## Local Storage Usage

For demonstration purposes, some data is stored in the browser's localStorage. In a production environment, this data would be stored in the database.

## License

This project is for educational purposes. Feel free to modify and use it as needed.
