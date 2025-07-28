document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const messageBox = document.getElementById('message-box');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(loginForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/nmims_quiz_app/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.status === 'success') {
                window.location.href = '/nmims_quiz_app/index.php';
            } else if (result.status === 'conflict') {
                // Show a confirmation dialog
                if (confirm(result.message)) {
                    // If user clicks OK, send a "force login" request
                    forceLogin(data);
                }
            } else {
                throw new Error(result.message || 'An unknown error occurred.');
            }
        } catch (error) {
            messageBox.textContent = `Error: ${error.message}`;
            messageBox.style.display = 'block';
        }
    });

    async function forceLogin(data) {
        data.force = true; // Add the force flag
        try {
            const response = await fetch('/nmims_quiz_app/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.status === 'success') {
                window.location.href = '/nmims_quiz_app/index.php';
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            messageBox.textContent = `Error: ${error.message}`;
            messageBox.style.display = 'block';
        }
    }
});
