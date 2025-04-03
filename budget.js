
document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.form-step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    const progressCircles = document.querySelectorAll('.circle');
    const submitButton = document.getElementById('budget-submit');
    let currentStep = 0;
    
    // Initialize the budget tool
    initBudgetTool();
    
    // Initialize the chart
    const ctx = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Income', 'Housing', 'Transportation', 'Education', 'Personal', 'Savings'],
            datasets: [{
                data: [0, 0, 0, 0, 0, 0],
                backgroundColor: ['#4CAF50', '#FF9800', '#2196F3', '#FFC107', '#E91E63', '#9C27B0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: 'white',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
    
    // Global object to store budget data
    const budgetData = {
        income: 0,
        housing: 0,
        transportation: 0,
        education: 0,
        personal: 0,
        savings: 0
    };
    
    // Handle next button clicks
    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            updateBudgetData();
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
                updateChart();
            }
        });
    });
    
    // Handle previous button clicks
    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });
    
    // Handle form submission
    if (submitButton) {
        submitButton.addEventListener('click', () => {
            updateBudgetData();
            updateChart();
            saveBudget();
            alert('Budget saved successfully!');
        });
    }
    
    // Show the current step and update progress
    function showStep(step) {
        steps.forEach((formStep, index) => {
            formStep.classList.toggle('active', index === step);
        });
        
        progressCircles.forEach((circle, index) => {
            circle.classList.toggle('active', index <= step);
        });
    }
    
    // Update the budget data object with form values
    function updateBudgetData() {
        if (currentStep === 0) {
            budgetData.income = parseFloat(document.getElementById('salary').value || 0) +
                                parseFloat(document.getElementById('other-income').value || 0);
        } else if (currentStep === 1) {
            budgetData.housing = parseFloat(document.getElementById('mortgage').value || 0) +
                                parseFloat(document.getElementById('rent').value || 0) +
                                parseFloat(document.getElementById('insurance').value || 0) +
                                parseFloat(document.getElementById('repairs').value || 0) +
                                parseFloat(document.getElementById('utilities').value || 0) +
                                parseFloat(document.getElementById('internet').value || 0) +
                                parseFloat(document.getElementById('phone').value || 0);
        } else if (currentStep === 2) {
            budgetData.transportation = parseFloat(document.getElementById('car-payment').value || 0) +
                                        parseFloat(document.getElementById('fuel').value || 0) +
                                        parseFloat(document.getElementById('car-insurance').value || 0) +
                                        parseFloat(document.getElementById('car-repairs').value || 0);
        } else if (currentStep === 3) {
            budgetData.education = parseFloat(document.getElementById('student-loan').value || 0) +
                                parseFloat(document.getElementById('books').value || 0) +
                                parseFloat(document.getElementById('college-tuition').value || 0);
        } else if (currentStep === 4) {
            budgetData.personal = parseFloat(document.getElementById('groceries').value || 0) +
                                parseFloat(document.getElementById('entertainment').value || 0) +
                                parseFloat(document.getElementById('clothing').value || 0) +
                                parseFloat(document.getElementById('medical').value || 0) +
                                parseFloat(document.getElementById('pet-supplies').value || 0) +
                                parseFloat(document.getElementById('other').value || 0);
        } else if (currentStep === 5) {
            budgetData.savings = parseFloat(document.getElementById('retirement').value || 0) +
                                parseFloat(document.getElementById('emergency-fund').value || 0) +
                                parseFloat(document.getElementById('investments').value || 0);
        }
        
        // Update net income display
        const totalExpenses = budgetData.housing + budgetData.transportation + 
                            budgetData.education + budgetData.personal + budgetData.savings;
        const netIncome = budgetData.income - totalExpenses;
        document.getElementById('net-income-amount').textContent = netIncome.toFixed(2);
    }
    
    // Update the pie chart with budget data
    function updateChart() {
        pieChart.data.datasets[0].data = [
            budgetData.income,
            budgetData.housing,
            budgetData.transportation,
            budgetData.education,
            budgetData.personal,
            budgetData.savings
        ];
        pieChart.update();
    }
    
    // Save budget data to local storage (in a real app, this would go to a database)
    function saveBudget() {
        // Create category-specific budget objects
        const budgets = [
            { category: 'housing', amount: budgetData.housing },
            { category: 'transportation', amount: budgetData.transportation },
            { category: 'education', amount: budgetData.education },
            { category: 'personal', amount: budgetData.personal },
            { category: 'savings', amount: budgetData.savings }
        ];
        
        // Save to local storage
        localStorage.setItem('budgets', JSON.stringify(budgets));
        localStorage.setItem('income', budgetData.income.toString());
    }
    
    // Initialize from saved data if available
    function initBudgetTool() {
        const savedBudgets = JSON.parse(localStorage.getItem('budgets')) || [];
        const savedIncome = parseFloat(localStorage.getItem('income') || '0');
        
        if (savedBudgets.length > 0 || savedIncome > 0) {
            // Pre-fill income fields
            document.getElementById('salary').value = savedIncome;
            
            // Extract category values from saved budgets
            const housingBudget = savedBudgets.find(b => b.category === 'housing')?.amount || 0;
            const transportBudget = savedBudgets.find(b => b.category === 'transportation')?.amount || 0;
            const educationBudget = savedBudgets.find(b => b.category === 'education')?.amount || 0;
            const personalBudget = savedBudgets.find(b => b.category === 'personal')?.amount || 0;
            const savingsBudget = savedBudgets.find(b => b.category === 'savings')?.amount || 0;
            
            // Update budget data object
            budgetData.income = savedIncome;
            budgetData.housing = housingBudget;
            budgetData.transportation = transportBudget;
            budgetData.education = educationBudget;
            budgetData.personal = personalBudget;
            budgetData.savings = savingsBudget;
            
            // Update net income display
            const totalExpenses = budgetData.housing + budgetData.transportation + 
                                budgetData.education + budgetData.personal + budgetData.savings;
            const netIncome = budgetData.income - totalExpenses;
            document.getElementById('net-income-amount').textContent = netIncome.toFixed(2);
            
            // Update chart
            updateChart();
        }
    }
    
    // Initialize the first step
    showStep(currentStep);
});
