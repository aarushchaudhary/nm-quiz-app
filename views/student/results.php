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

  function calculate_percentage($count, $total) {
      return ($total > 0) ? round(($count / $total) * 100) : 0;
  }
?>

<div class="lobby-container">
    <h2>Results for: <span style="color: #e60000;"><?php echo htmlspecialchars($attempt['quiz_title']); ?></span></h2>
    
    <div style="display: flex; justify-content: space-around; align-items: center; margin: 30px 0;">
        <div>
            <h3>Your Score</h3>
            <p style="font-size: 28px; font-weight: bold; color: #28a745; margin:0;">
                <?php echo htmlspecialchars(number_format($attempt['total_score'], 2)); ?>
            </p>
        </div>
        <div>
            <h3>Total Questions</h3>
            <p style="font-size: 28px; font-weight: bold; color: #333; margin:0;">
                <?php echo $total_questions; ?>
            </p>
        </div>
    </div>

    <!-- Pure CSS Bar Chart Visualization -->
    <div class="css-chart-container">
        <h4>Performance Breakdown</h4>
        <ul class="css-chart">
            <li>
                <span class="bar-label">Correct (<?php echo $correct_count; ?>)</span>
                <span class="bar">
                    <span class="bar-fill correct" style="width: <?php echo calculate_percentage($correct_count, $total_questions); ?>%;">
                        <?php echo calculate_percentage($correct_count, $total_questions); ?>%
                    </span>
                </span>
            </li>
            <li>
                <span class="bar-label">Incorrect (<?php echo $incorrect_count; ?>)</span>
                <span class="bar">
                    <span class="bar-fill incorrect" style="width: <?php echo calculate_percentage($incorrect_count, $total_questions); ?>%;">
                        <?php echo calculate_percentage($incorrect_count, $total_questions); ?>%
                    </span>
                </span>
            </li>
            <li>
                <span class="bar-label">Unanswered (<?php echo $unanswered_count; ?>)</span>
                <span class="bar">
                    <span class="bar-fill unanswered" style="width: <?php echo calculate_percentage($unanswered_count, $total_questions); ?>%;">
                        <?php echo calculate_percentage($unanswered_count, $total_questions); ?>%
                    </span>
                </span>
            </li>
        </ul>
    </div>

    <a href="detailed_results.php?attempt_id=<?php echo htmlspecialchars($attempt_id); ?>" class="button-red" style="width: auto; padding: 12px 30px; margin-top: 10px; text-decoration: none; background-color: #17a2b8;">View Detailed Breakdown</a>
    <a href="dashboard.php" class="button-red" style="width: auto; padding: 12px 30px; margin-top: 20px; text-decoration: none;">Back to Dashboard</a>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
