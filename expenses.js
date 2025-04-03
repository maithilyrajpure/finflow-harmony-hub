
document.addEventListener('DOMContentLoaded', function() {
    // Load expenses from the server or local storage
    loadExpenses();
    
    // Set up form submission
    const expenseForm = document.getElementById('expense-form');
    if (expenseForm) {
        expenseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const expenseData = {
                date: document.getElementById('expense_date').value,
                name: document.getElementById('expense_name').value,
                category: document.getElementById('expense_category').value,
                amount: document.getElementById('expense_amount').value,
                description: document.getElementById('expense_description').value
            };
            
            // Normally we would send this data to the server via AJAX
            // For this demo, we'll use local storage
            saveExpense(expenseData);
            
            // Reset form
            expenseForm.reset();
        });
    }
});

// Load expenses from local storage for demo purposes
function loadExpenses() {
    const tableBody = document.getElementById('expense-table-body');
    if (!tableBody) return;
    
    // Clear the table first
    tableBody.innerHTML = '';
    
    // Get expenses from local storage
    let expenses = JSON.parse(localStorage.getItem('expenses')) || [];
    
    // Sort expenses by date (newest first)
    expenses.sort((a, b) => new Date(b.date) - new Date(a.date));
    
    // Add expenses to table
    expenses.forEach((expense, index) => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${formatDate(expense.date)}</td>
            <td>${expense.name}</td>
            <td>${expense.category}</td>
            <td>$${parseFloat(expense.amount).toFixed(2)}</td>
            <td>${expense.description || '-'}</td>
            <td>
                <div class="expense-actions">
                    <button class="btn-delete" onclick="deleteExpense(${index})">×</button>
                </div>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // If there are no expenses, display a message
    if (expenses.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="7" style="text-align: center;">No expenses found. Add one above!</td>';
        tableBody.appendChild(row);
    }
    
    // Check budget alerts after loading expenses
    checkBudgetAlerts();
}

// Save a new expense to local storage
function saveExpense(expense) {
    // Get existing expenses
    let expenses = JSON.parse(localStorage.getItem('expenses')) || [];
    
    // Add the new expense
    expenses.push(expense);
    
    // Save back to local storage
    localStorage.setItem('expenses', JSON.stringify(expenses));
    
    // Reload the expenses table
    loadExpenses();
}

// Delete an expense from local storage
function deleteExpense(index) {
    // Get existing expenses
    let expenses = JSON.parse(localStorage.getItem('expenses')) || [];
    
    // Remove the expense at the specified index
    expenses.splice(index, 1);
    
    // Save back to local storage
    localStorage.setItem('expenses', JSON.stringify(expenses));
    
    // Reload the expenses table
    loadExpenses();
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

// Check if expenses exceed budget and show alert
function checkBudgetAlerts() {
    // Get budgets and expenses
    const budgets = JSON.parse(localStorage.getItem('budgets')) || [];
    const expenses = JSON.parse(localStorage.getItem('expenses')) || [];
    
    if (budgets.length === 0 || expenses.length === 0) return;
    
    // Get current month and year
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // JavaScript months are 0-indexed
    const currentYear = today.getFullYear();
    
    // Filter expenses for current month
    const currentMonthExpenses = expenses.filter(expense => {
        const expenseDate = new Date(expense.date);
        return (expenseDate.getMonth() + 1) === currentMonth && 
               expenseDate.getFullYear() === currentYear;
    });
    
    // Calculate total expenses by category
    const expensesByCategory = {};
    currentMonthExpenses.forEach(expense => {
        const category = expense.category;
        if (!expensesByCategory[category]) {
            expensesByCategory[category] = 0;
        }
        expensesByCategory[category] += parseFloat(expense.amount);
    });
    
    // Check if any category exceeds budget
    budgets.forEach(budget => {
        const categoryExpense = expensesByCategory[budget.category] || 0;
        
        if (categoryExpense > parseFloat(budget.amount)) {
            // Show alert for the exceeded budget
            showBudgetAlert(`You've exceeded your budget for ${budget.category} by $${(categoryExpense - parseFloat(budget.amount)).toFixed(2)}.`);
        }
    });
}
