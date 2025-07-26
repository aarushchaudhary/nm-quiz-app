<?php
  $pageTitle = 'Exam Lobby';
  $customCSS = 'exam.css';
  
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization & Input Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
      header('Location: dashboard.php?error=invalid_quiz');
      exit();
  }

  $quiz_id = $_GET['id'];
  $student_user_id = $_SESSION['user_id'];

  // --- Fetch Quiz Details ---
  $stmt_quiz = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
  $stmt_quiz->execute([$quiz_id]);
  $quiz = $stmt_quiz->fetch();

  if (!$quiz) {
      header('Location: dashboard.php?error=quiz_not_found');
      exit();
  }

  // --- NEW: Register student in the lobby ---
  // Using INSERT IGNORE to prevent errors if the student refreshes the page
  // and is already in the lobby for this quiz.
  $lobby_sql = "INSERT IGNORE INTO quiz_lobby (quiz_id, student_id) VALUES (?, ?)";
  $stmt_lobby = $pdo->prepare($lobby_sql);
  $stmt_lobby->execute([$quiz_id, $student_user_id]);
?>

<div class="lobby-container">
    <h2>Exam Lobby</h2>
    <p class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></p>

    <p class="lobby-instructions">
        You have successfully joined the lobby. The exam will begin automatically as soon as the faculty starts the session. Please do not close or refresh this page.
    </p>

    <div class="spinner"></div>

    <p class="status-text" id="status-text">Waiting for faculty to start the exam...</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quizId = <?php echo json_encode($quiz_id); ?>;
    const statusText = document.getElementById('status-text');

    async function checkQuizStatus() {
        try {
            const response = await fetch(`/nmims_quiz_app/api/shared/get_quiz_status.php?id=${quizId}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();

            if (data.status === 'In Progress') {
                window.location.href = `exam.php?id=${quizId}`;
            } else if (data.status) {
                statusText.textContent = `Status: ${data.status}. Waiting...`;
            }
        } catch (error) {
            console.error('Error checking quiz status:', error);
            statusText.textContent = 'Connection error. Retrying...';
        }
    }

    const statusInterval = setInterval(checkQuizStatus, 5000);
    checkQuizStatus(); // Initial check
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
