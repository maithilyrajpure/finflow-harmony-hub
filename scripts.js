
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication state
    checkAuthState();
    
    // Initialize dashboard if on index page
    if (document.querySelector('.dashboard-container')) {
        initializeDashboard();
    }
    
    // Setup logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
});

// Function to check if user is logged in
function checkAuthState() {
    const isLoggedIn = localStorage.getItem('userLoggedIn') === 'true';
    const userName = localStorage.getItem('userName');
    
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const usernameElement = document.getElementById('username');
    
    if (isLoggedIn && userName) {
        // User is logged in
        if (usernameElement) usernameElement.textContent = userName;
        if (loginBtn) loginBtn.style.display = 'none';
        if (logoutBtn) logoutBtn.style.display = 'inline-block';
        
        // Redirect from login/register pages if already logged in
        if (window.location.pathname.includes('login.html') || 
            window.location.pathname.includes('register.html')) {
            window.location.href = 'index.html';
        }
    } else {
        // User is not logged in
        if (usernameElement) usernameElement.textContent = 'Guest';
        if (loginBtn) loginBtn.style.display = 'inline-block';
        if (logoutBtn) logoutBtn.style.display = 'none';
        
        // Redirect to login for protected pages
        const currentPage = window.location.pathname;
        const publicPages = ['login.html', 'register.html'];
        
        if (!publicPages.some(page => currentPage.includes(page)) && 
            !currentPage.endsWith('/') && 
            !currentPage.endsWith('index.html')) {
            window.location.href = 'login.html';
        }
    }
}

// Function to handle logout
function logout() {
    // Clear localStorage
    localStorage.removeItem('userLoggedIn');
    localStorage.removeItem('userName');
    
    // Make AJAX request to destroy PHP session
    fetch('logout.php')
    .finally(() => {
        // Redirect to login page
        window.location.href = 'login.html';
    });
}

// Function to initialize dashboard
function initializeDashboard() {
    // Create sample charts for the dashboard
    createSampleExpensesChart();
    createSampleBudgetChart();
    createSampleDebtSummary();
    
    // Check for budget alerts
    checkBudgetAlerts();
}

// Sample chart data functions
function createSampleExpensesChart() {
    const ctx = document.getElementById('expensesChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Food', 'Rent', 'Utilities', 'Entertainment', 'Transport'],
            datasets: [{
                data: [300, 800, 200, 150, 250],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function createSampleBudgetChart() {
    const ctx = document.getElementById('budgetChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Income', 'Expenses', 'Savings'],
            datasets: [{
                label: 'Monthly Overview',
                data: [2500, 1700, 800],
                backgroundColor: [
                    '#4CAF50', '#FF5722', '#2196F3'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createSampleDebtSummary() {
    const debtSummary = document.getElementById('debtSummary');
    if (!debtSummary) return;
    
    debtSummary.innerHTML = `
        <div class="debt-progress">
            <p>Student Loan: $15,000</p>
            <div class="progress-bar">
                <div class="progress" style="width: 25%"></div>
            </div>
            <p class="progress-text">25% paid off</p>
        </div>
        <div class="debt-progress">
            <p>Car Loan: $8,000</p>
            <div class="progress-bar">
                <div class="progress" style="width: 40%"></div>
            </div>
            <p class="progress-text">40% paid off</p>
        </div>
    `;
}

function checkBudgetAlerts() {
    // Sample alert check - in real app this would compare actual data
    const totalExpenses = 1700;
    const budgetLimit = 1500;
    
    if (totalExpenses > budgetLimit) {
        showBudgetAlert(`You've exceeded your monthly budget by $${totalExpenses - budgetLimit}.`);
    }
}

function showBudgetAlert(message) {
    const modal = document.getElementById('alertModal');
    const alertMessage = document.getElementById('alertMessage');
    const closeBtn = document.querySelector('.close');
    
    if (modal && alertMessage) {
        alertMessage.textContent = message;
        modal.style.display = 'block';
        
        // Close modal when clicking X
        if (closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}
