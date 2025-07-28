<?php
  $pageTitle = 'Student Dashboard';
  $customCSS = 'manage.css'; 
  
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  
  $student_user_id = $_SESSION['user_id'];
  // **FIX:** Changed 'name' to 'full_name' to match the session variable from the login script.
  $studentName = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Student';

  // --- Fetch the Student's Course ID and Graduation Year ---
  $stmt_student = $pdo->prepare("SELECT course_id, graduation_year FROM students WHERE user_id = ?");
  $stmt_student->execute([$student_user_id]);
  $student_info = $stmt_student->fetch();
  
  $student_course_id = $student_info ? $student_info['course_id'] : null;
  $student_grad_year = $student_info ? $student_info['graduation_year'] : null;
  $quizzes = [];

  // --- Fetch Available Quizzes ---
  if ($student_course_id && $student_grad_year) {
      $sql = "SELECT 
                q.id, 
                q.title, 
                q.start_time,
                es.name as status_name
              FROM quizzes q
              JOIN exam_statuses es ON q.status_id = es.id
              WHERE q.course_id = :course_id 
              AND q.graduation_year = :graduation_year
              AND (
                (NOW() BETWEEN q.start_time AND q.end_time AND es.name != 'Completed')
                OR
                (es.name IN ('Lobby Open', 'In Progress'))
              )
              ORDER BY q.start_time ASC";
      
      $stmt_quizzes = $pdo->prepare($sql);
      $stmt_quizzes->execute([
          ':course_id' => $student_course_id,
          ':graduation_year' => $student_grad_year
      ]);
      $quizzes = $stmt_quizzes->fetchAll();
  }
?>

<div class="manage-container">
    <h2 style="margin-bottom: 10px;">Welcome, <?php echo $studentName; ?>!</h2>
    <p style="text-align:center; color: #555; margin-top:0;">The quizzes listed below are currently available for you to join.</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Quiz Title</th>
                <th>Starts At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($quizzes)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 20px;">There are no active quizzes available for your batch at this moment.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo date('M j, Y, g:i A', strtotime($quiz['start_time'])); ?></td>
                        <td>
                            <?php $status_class = strtolower(str_replace(' ', '_', $quiz['status_name'])); ?>
                            <span class="status-badge status-<?php echo htmlspecialchars($status_class); ?>">
                                <?php echo htmlspecialchars($quiz['status_name']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <?php
                                if ($quiz['status_name'] != 'Not Started') {
                                    echo '<a href="lobby.php?id=' . $quiz['id'] . '" class="btn-manage" style="background-color: #28a745;">Join Exam</a>';
                                } else {
                                    echo '<span style="color: #6c757d;">Waiting for faculty...</span>';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
