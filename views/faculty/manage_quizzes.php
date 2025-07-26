<?php
  $pageTitle = 'Manage Quizzes';
  $customCSS = 'manage.css'; 
  
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  $faculty_id = $_SESSION['user_id'];

  // --- Fetch Quizzes for this Faculty ---
  $sql = "SELECT 
            q.id, 
            q.title, 
            q.start_time, 
            c.name as course_name,
            es.name as status_name
          FROM quizzes q
          JOIN courses c ON q.course_id = c.id
          JOIN exam_statuses es ON q.status_id = es.id
          WHERE q.faculty_id = :faculty_id
          ORDER BY q.created_at DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':faculty_id' => $faculty_id]);
  $quizzes = $stmt->fetchAll();
?>

<div class="manage-container">
    <h2>My Quizzes</h2>

    <?php
    if (isset($_GET['success'])) {
        echo '<div class="message-box success-message">' . htmlspecialchars($_GET['success']) . '</div>';
    }
    ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Course</th>
                <th>Start Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($quizzes)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">You have not created any quizzes yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['course_name']); ?></td>
                        <td><?php echo date('M j, Y, g:i A', strtotime($quiz['start_time'])); ?></td>
                        <td>
                            <?php $status_class = strtolower(str_replace(' ', '_', $quiz['status_name'])); ?>
                            <span class="status-badge status-<?php echo htmlspecialchars($status_class); ?>">
                                <?php echo htmlspecialchars($quiz['status_name']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <!-- Button to Add/Edit Questions -->
                            <a href="view_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-manage">Manage Questions</a>
                            
                            <!-- NEW: Button to Start/Control the Live Exam -->
                            <a href="start_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-start-quiz">Start Quiz</a>
                            
                            <!-- Button to Edit Quiz Details -->
                            <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-edit">Edit Details</a>
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
