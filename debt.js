
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the debt tracker
    initDebtTracker();
    
    // Set up strategy buttons
    const strategyButtons = document.querySelectorAll('.strategy-btn');
    strategyButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            strategyButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all strategy descriptions
            document.querySelectorAll('.strategy-detail').forEach(desc => {
                desc.classList.remove('active');
            });
            
            // Show the selected strategy description
            const strategyType = this.getAttribute('data-strategy');
            document.getElementById(strategyType + '-description').classList.add('active');
            
            // Recalculate payment plan if we have debt data
            if (getDebts().length > 0) {
                calculatePaymentPlan();
            }
        });
    });
    
    // Set up save button
    const saveDebtBtn = document.getElementById('saveDebtBtn');
    if (saveDebtBtn) {
        saveDebtBtn.addEventListener('click', function() {
            saveDebts();
            updateDebtSummary();
            alert('Debt information saved successfully!');
        });
    }
    
    // Set up calculate button
    const calculatePlanBtn = document.getElementById('calculatePlanBtn');
    if (calculatePlanBtn) {
        calculatePlanBtn.addEventListener('click', function() {
            calculatePaymentPlan();
        });
    }
});

// Function to add a new row to the debt table
function addRow() {
    const tableBody = document.getElementById('debtTableBody');
    if (!tableBody) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" placeholder="Debt Type" class="debtType" /></td>
        <td><input type="number" placeholder="Amount Owed" class="amountOwed" /></td>
        <td><input type="number" placeholder="Interest Rate" class="interestRate" /></td>
        <td><input type="number" placeholder="Minimum Payment" class="minPayment" /></td>
        <td><input type="number" placeholder="Progress" class="progressInput" /></td>
        <td>In Progress</td>
        <td><button class="btn-delete" onclick="deleteRow(this)">×</button></td>
    `;
    
    tableBody.appendChild(newRow);
}

// Function to delete a row from the debt table
function deleteRow(button) {
    const row = button.closest('tr');
    row.parentNode.removeChild(row);
    
    // Make sure we always have at least one row
    const tableBody = document.getElementById('debtTableBody');
    if (tableBody && tableBody.children.length === 0) {
        addRow();
    }
}

// Function to collect debt data from the table
function getDebts() {
    const rows = document.querySelectorAll('#debtTableBody tr');
    const debts = [];
    
    rows.forEach(row => {
        const debtType = row.querySelector('.debtType').value;
        const amountOwed = parseFloat(row.querySelector('.amountOwed').value) || 0;
        const interestRate = parseFloat(row.querySelector('.interestRate').value) || 0;
        const minPayment = parseFloat(row.querySelector('.minPayment').value) || 0;
        const progress = parseFloat(row.querySelector('.progressInput').value) || 0;
        
        // Only include rows with debt type and amount
        if (debtType && amountOwed > 0) {
            debts.push({
                debtType,
                amountOwed,
                interestRate,
                minPayment,
                progress
            });
        }
    });
    
    return debts;
}

// Function to save debt data to local storage
function saveDebts() {
    const debts = getDebts();
    localStorage.setItem('debts', JSON.stringify(debts));
}

// Function to load debt data from local storage
function loadDebts() {
    const debts = JSON.parse(localStorage.getItem('debts')) || [];
    
    // Clear default row if we have saved debts
    if (debts.length > 0) {
        const tableBody = document.getElementById('debtTableBody');
        if (tableBody) {
            tableBody.innerHTML = ''; // Clear all rows
            
            // Add each debt as a row
            debts.forEach(debt => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="text" value="${debt.debtType}" class="debtType" /></td>
                    <td><input type="number" value="${debt.amountOwed}" class="amountOwed" /></td>
                    <td><input type="number" value="${debt.interestRate}" class="interestRate" /></td>
                    <td><input type="number" value="${debt.minPayment}" class="minPayment" /></td>
                    <td><input type="number" value="${debt.progress}" class="progressInput" /></td>
                    <td>In Progress</td>
                    <td><button class="btn-delete" onclick="deleteRow(this)">×</button></td>
                `;
                tableBody.appendChild(row);
            });
        }
    }
}

// Function to update debt summary statistics
function updateDebtSummary() {
    const debts = getDebts();
    
    // Calculate total debt and weighted average interest rate
    let totalDebt = 0;
    let weightedInterest = 0;
    let totalMinPayment = 0;
    
    debts.forEach(debt => {
        totalDebt += debt.amountOwed;
        weightedInterest += (debt.amountOwed * debt.interestRate);
        totalMinPayment += debt.minPayment;
    });
    
    const avgInterest = debts.length > 0 ? weightedInterest / totalDebt : 0;
    
    // Update summary display
    document.getElementById('totalDebt').textContent = '$' + totalDebt.toFixed(2);
    document.getElementById('avgInterest').textContent = avgInterest.toFixed(2) + '%';
    document.getElementById('monthlyPayment').textContent = '$' + totalMinPayment.toFixed(2);
    
    // Estimate debt-free date (very simple calculation)
    if (totalDebt > 0 && totalMinPayment > 0) {
        const monthsToPayOff = Math.ceil(totalDebt / totalMinPayment);
        const today = new Date();
        const estimatedFreeDate = new Date(today);
        estimatedFreeDate.setMonth(today.getMonth() + monthsToPayOff);
        
        document.getElementById('debtFreeDate').textContent = estimatedFreeDate.toLocaleDateString(undefined, { 
            year: 'numeric', 
            month: 'long'
        });
    } else {
        document.getElementById('debtFreeDate').textContent = '-';
    }
}

// Function to calculate payment plan based on selected strategy
function calculatePaymentPlan() {
    let debts = getDebts();
    if (debts.length === 0) {
        alert('Please add at least one debt to calculate a payment plan.');
        return;
    }
    
    // Determine which strategy is selected
    const snowballStrategy = document.querySelector('.strategy-btn[data-strategy="snowball"]').classList.contains('active');
    
    // Sort debts according to strategy
    if (snowballStrategy) {
        // Snowball: Smallest balance first
        debts.sort((a, b) => a.amountOwed - b.amountOwed);
    } else {
        // Avalanche: Highest interest rate first
        debts.sort((a, b) => b.interestRate - a.interestRate);
    }
    
    // Generate payment plan HTML
    const paymentPlanElement = document.getElementById('paymentPlan');
    paymentPlanElement.innerHTML = '';
    
    // Create a step for each debt
    debts.forEach((debt, index) => {
        const step = document.createElement('div');
        step.className = 'payment-step';
        
        const estimatedMonths = Math.ceil(debt.amountOwed / debt.minPayment);
        
        step.innerHTML = `
            <h4>Step ${index + 1}: Pay off ${debt.debtType}</h4>
            <p><strong>Amount:</strong> $${debt.amountOwed.toFixed(2)}</p>
            <p><strong>Interest Rate:</strong> ${debt.interestRate.toFixed(2)}%</p>
            <p><strong>Minimum Payment:</strong> $${debt.minPayment.toFixed(2)}</p>
            <p><strong>Estimated Payoff:</strong> ${estimatedMonths} months</p>
            <p>Focus on paying this debt while making minimum payments on all others.</p>
        `;
        
        paymentPlanElement.appendChild(step);
    });
    
    // Add final note
    const finalNote = document.createElement('div');
    finalNote.className = 'payment-step';
    finalNote.innerHTML = `
        <h4>Final Step: Debt-Free!</h4>
        <p>Once all debts are paid off, consider redirecting those payments to savings and investments.</p>
        <p>This will help build wealth and prevent future debt.</p>
    `;
    paymentPlanElement.appendChild(finalNote);
}

// Initialize the debt tracker with saved data if available
function initDebtTracker() {
    loadDebts();
    updateDebtSummary();
}
