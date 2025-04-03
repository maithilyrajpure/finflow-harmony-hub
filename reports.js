
document.addEventListener('DOMContentLoaded', function() {
    // Initialize reports page components
    initializeReportsPage();
    
    // Setup date range selector
    const dateRangeSelect = document.getElementById('date-range');
    const customDateRange = document.getElementById('custom-date-range');
    
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.style.display = 'inline-block';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    }
    
    // Setup filter application
    const applyFilterBtn = document.getElementById('apply-filter');
    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', function() {
            // This would typically fetch new data based on date range
            generateReports();
        });
    }
    
    // Setup download buttons
    const downloadPdfBtn = document.getElementById('download-pdf');
    const exportCsvBtn = document.getElementById('export-csv');
    
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', function() {
            alert('PDF download functionality would be implemented here.');
        });
    }
    
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function() {
            alert('CSV export functionality would be implemented here.');
        });
    }
    
    // Generate initial reports
    generateReports();
});

function initializeReportsPage() {
    // Set current date for date inputs
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    
    if (startDateInput && endDateInput) {
        startDateInput.valueAsDate = firstDayOfMonth;
        endDateInput.valueAsDate = today;
    }
}

function generateReports() {
    // Create the charts for the reports page
    createIncomeVsExpensesChart();
    createExpenseBreakdownChart();
    createMonthlyTrendChart();
    createBudgetAdherenceChart();
    
    // Update summary statistics
    updateSummaryStats();
}

function createIncomeVsExpensesChart() {
    const ctx = document.getElementById('incomeVsExpenses');
    if (!ctx) return;
    
    // Sample data - in a real app this would be retrieved from the database
    const chartData = {
        labels: ['Income', 'Expenses'],
        datasets: [{
            data: [3500, 2100],
            backgroundColor: ['#4CAF50', '#FF5722'],
            borderWidth: 0
        }]
    };
    
    new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function createExpenseBreakdownChart() {
    const ctx = document.getElementById('expenseBreakdown');
    if (!ctx) return;
    
    // Sample data - in a real app this would be retrieved from the database
    const chartData = {
        labels: ['Housing', 'Food', 'Transport', 'Utilities', 'Entertainment', 'Others'],
        datasets: [{
            data: [800, 400, 300, 200, 250, 150],
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ],
            borderWidth: 0
        }]
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    });
}

function createMonthlyTrendChart() {
    const ctx = document.getElementById('monthlyTrend');
    if (!ctx) return;
    
    // Sample data - in a real app this would be retrieved from the database
    const chartData = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [
            {
                label: 'Income',
                data: [3200, 3300, 3300, 3400, 3500, 3500],
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                tension: 0.3,
                fill: true
            },
            {
                label: 'Expenses',
                data: [2500, 2300, 2400, 2200, 2100, 2100],
                borderColor: '#FF5722',
                backgroundColor: 'rgba(255, 87, 34, 0.2)',
                tension: 0.3,
                fill: true
            }
        ]
    };
    
    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    });
}

function createBudgetAdherenceChart() {
    const ctx = document.getElementById('budgetAdherence');
    if (!ctx) return;
    
    // Sample data - in a real app this would be retrieved from the database
    const chartData = {
        labels: ['Housing', 'Food', 'Transport', 'Utilities', 'Entertainment', 'Others'],
        datasets: [
            {
                label: 'Budget',
                data: [900, 450, 350, 200, 200, 200],
                backgroundColor: 'rgba(76, 175, 80, 0.7)'
            },
            {
                label: 'Actual',
                data: [800, 400, 300, 200, 250, 150],
                backgroundColor: 'rgba(255, 193, 7, 0.7)'
            }
        ]
    };
    
    new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(255, 255, 255, 0.7)'
                    }
                }
            }
        }
    });
}

function updateSummaryStats() {
    // Sample data - in a real app these would be calculated from actual data
    const income = 3500;
    const expenses = 2100;
    const savings = income - expenses;
    const savingRate = Math.round((savings / income) * 100);
    
    // Update the DOM elements
    const totalIncomeElement = document.getElementById('total-income');
    const totalExpensesElement = document.getElementById('total-expenses');
    const totalSavingsElement = document.getElementById('total-savings');
    const savingRateElement = document.getElementById('saving-rate');
    
    if (totalIncomeElement) totalIncomeElement.textContent = '$' + income.toLocaleString();
    if (totalExpensesElement) totalExpensesElement.textContent = '$' + expenses.toLocaleString();
    if (totalSavingsElement) totalSavingsElement.textContent = '$' + savings.toLocaleString();
    if (savingRateElement) savingRateElement.textContent = savingRate + '%';
}
