  document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    login.php
    forgot_password.php
    const forgotPasswordLink = document.querySelector('a[href="forgot_password.php"]');
    const socialIcons = document.querySelectorAll('.social-icons a');

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const emailInput = loginForm.querySelector('input[name="email"]');
            const passwordInput = loginForm.querySelector('input[name="password"]');

            if (!emailInput.value || !passwordInput.value) {
                alert('Please fill in all fields.');
                event.preventDefault();
                return;
            }

            if (emailInput.value && !emailInput.value.includes('@')) {
                alert('Please enter a valid email address.');
                event.preventDefault();
                return;
            }

            // Example of connecting to an external API (replace with your actual API endpoint)
            /*
            fetch('your_api_endpoint', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: emailInput.value,
                    password: passwordInput.value,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect or show success message
                    window.location.href = 'dashboard.php'; // Example redirect
                } else {
                    alert('Login failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during login.');
            });
            event.preventDefault(); // Prevent default form submission while using fetch
            */

            // Or if you are using the php backend:
            // the form will submit to login_process.php and that php file will handle the connection to the database.
        });
    }

    if (forgotPasswordLink) {
        // No client-side handling needed, as it redirects to forgot_password.php
    }

    if (socialIcons) {
        socialIcons.forEach(icon => {
            icon.addEventListener('click', function(event) {
                event.preventDefault();
                const iconName = icon.querySelector('i').classList[1].split('-')[1];
                alert(`${iconName} login not implemented.`);
            });
        });
    }
});
