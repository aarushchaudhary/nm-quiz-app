<?php
  $pageTitle = 'Exam Results';
  $customCSS = 'exam.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization & Input Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4 || !isset($_GET['attempt_id'])) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  $attempt_id = filter_var($_GET['attempt_id'], FILTER_VALIDATE_INT);
  $student_user_id = $_SESSION['user_id'];

  // --- Fetch Attempt Details ---
  $sql = "SELECT sa.*, q.title as quiz_title, q.config_easy_count, q.config_medium_count, q.config_hard_count
          FROM student_attempts sa
          JOIN quizzes q ON sa.quiz_id = q.id
          WHERE sa.id = ? AND sa.student_id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$attempt_id, $student_user_id]);
  $attempt = $stmt->fetch();

  if (!$attempt) {
      header('Location: dashboard.php?error=attempt_not_found');
      exit();
  }
  
  // --- Fetch Performance Breakdown ---
  $stmt_correct = $pdo->prepare("SELECT COUNT(*) FROM student_answers WHERE attempt_id = ? AND is_correct = 1");
  $stmt_correct->execute([$attempt_id]);
  $correct_count = $stmt_correct->fetchColumn();

  $stmt_incorrect = $pdo->prepare("SELECT COUNT(*) FROM student_answers WHERE attempt_id = ? AND is_correct = 0");
  $stmt_incorrect->execute([$attempt_id]);
  $incorrect_count = $stmt_incorrect->fetchColumn();
  
  $total_questions = $attempt['config_easy_count'] + $attempt['config_medium_count'] + $attempt['config_hard_count'];
  $answered_count = $correct_count + $incorrect_count;
  $unanswered_count = $total_questions - $answered_count;

  // **NEW:** Calculate Time Taken and Accuracy
  $time_taken_str = 'N/A';
  if ($attempt['started_at'] && $attempt['submitted_at']) {
      $start_time = new DateTime($attempt['started_at']);
      $end_time = new DateTime($attempt['submitted_at']);
      $interval = $start_time->diff($end_time);
      $time_taken_str = $interval->format('%im %ss');
  }
  $accuracy = ($answered_count > 0) ? round(($correct_count / $answered_count) * 100) : 0;
?>

<div class="lobby-container">
    <h2>Results for: <span style="color: #e60000;"><?php echo htmlspecialchars($attempt['quiz_title']); ?></span></h2>
    
    <div class="summary-grid">
        <div class="summary-item">
            <h3>Your Score</h3>
            <p style="color: #28a745;"><?php echo htmlspecialchars(number_format($attempt['total_score'], 2)); ?></p>
        </div>
        <div class="summary-item">
            <h3>Accuracy</h3>
            <p style="color: #007bff;"><?php echo $accuracy; ?>%</p>
        </div>
        <div class="summary-item">
            <h3>Time Taken</h3>
            <p style="color: #333; font-size: 24px;"><?php echo $time_taken_str; ?></p>
        </div>
        <div class="summary-item">
            <h3>Total Questions</h3>
            <p style="color: #333;"><?php echo $total_questions; ?></p>
        </div>
    </div>

    <div style="width: 100%; max-width: 350px; margin: 20px auto;">
        <canvas id="resultsChart"></canvas>
    </div>

    <a href="detailed_results.php?attempt_id=<?php echo htmlspecialchars($attempt_id); ?>" class="button-red" style="width: auto; padding: 12px 30px; margin-top: 10px; text-decoration: none; background-color: #17a2b8;">View Detailed Breakdown</a>
    <a href="dashboard.php" class="button-red" style="width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none;">Back to Dashboard</a>
</div>

<script src="/nmims_quiz_app/lib/chartjs/chart.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('resultsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Correct', 'Incorrect', 'Unanswered'],
            datasets: [{
                label: 'Performance',
                data: [
                    <?php echo json_encode($correct_count); ?>,
                    <?php echo json_encode($incorrect_count); ?>,
                    <?php echo json_encode($unanswered_count); ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Your Performance Breakdown'
                }
            }
        }
    });
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
