<?php
  $pageTitle = 'Exam Disqualified';
  $customCSS = 'exam.css';
  require_once '../../assets/templates/header.php';

  $attempt_id = isset($_GET['attempt_id']) ? filter_var($_GET['attempt_id'], FILTER_VALIDATE_INT) : null;
?>

<div class="lobby-container">
    <h2 style="color: #dc3545;">Exam Session Locked</h2>
    <p class="lobby-instructions">
        Your exam has been locked due to leaving the exam window multiple times.
        Please wait for the faculty in charge to review your case. If approved, you will be able to resume your exam.
    </p>

    <div class="spinner"></div>

    <p class="status-text" id="status-text">Status: Disqualified. Awaiting faculty review...</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const attemptId = <?php echo json_encode($attempt_id); ?>;
    if (!attemptId) {
        document.getElementById('status-text').textContent = 'Error: No attempt ID found.';
        return;
    }

    async function checkResumeStatus() {
        try {
            // This is a new API endpoint we will create next
            const response = await fetch(`/nmims_quiz_app/api/student/get_attempt_status.php?id=${attemptId}`);
            const data = await response.json();

            if (data.can_resume) {
                // If faculty re-enables, redirect back to the exam
                alert('The faculty has re-enabled your exam. You can now continue.');
                // We need to pass the quiz_id back to the exam page
                window.location.href = `exam.php?id=${data.quiz_id}`;
            }
        } catch (error) {
            console.error('Error checking status:', error);
        }
    }

    setInterval(checkResumeStatus, 5000); // Check every 5 seconds
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
