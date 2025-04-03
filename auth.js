
// Function to handle login form submission
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageElement = document.getElementById('loginMessage');
            
            // Create form data
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            // Send AJAX request
            fetch('login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageElement.innerHTML = '<p class="success-message">' + data.message + '</p>';
                    // Store user info in localStorage for client-side auth
                    localStorage.setItem('userLoggedIn', 'true');
                    localStorage.setItem('userName', data.name);
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    messageElement.innerHTML = '<p class="error-message">' + data.message + '</p>';
                }
            })
            .catch(error => {
                messageElement.innerHTML = '<p class="error-message">An error occurred. Please try again.</p>';
                console.error('Error:', error);
            });
        });
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const messageElement = document.getElementById('registerMessage');
            
            // Validate password match
            if (password !== confirmPassword) {
                messageElement.innerHTML = '<p class="error-message">Passwords do not match</p>';
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);
            
            // Send AJAX request
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageElement.innerHTML = '<p class="success-message">' + data.message + '</p>';
                    // Store user info in localStorage for client-side auth
                    localStorage.setItem('userLoggedIn', 'true');
                    localStorage.setItem('userName', data.name);
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    messageElement.innerHTML = '<p class="error-message">' + data.message + '</p>';
                }
            })
            .catch(error => {
                messageElement.innerHTML = '<p class="error-message">An error occurred. Please try again.</p>';
                console.error('Error:', error);
            });
        });
    }
});
