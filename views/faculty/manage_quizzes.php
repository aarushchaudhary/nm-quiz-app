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

<div class="confirm-modal-overlay" id="delete-quiz-modal">
    <div class="confirm-modal">
        <h3>Confirm Quiz Deletion</h3>
        <p>Are you sure you want to permanently delete this quiz? All associated questions, student attempts, and results will be lost forever. This action cannot be undone.</p>
        <div class="button-group">
            <button class="btn-cancel" id="cancel-delete-btn">Cancel</button>
            <button class="btn-confirm-delete" id="confirm-delete-btn">Yes, Delete Quiz</button>
        </div>
    </div>
</div>

<div class="manage-container">
    <h2>My Quizzes</h2>
    <?php if (isset($_GET['success'])) { echo '<div class="message-box success-message">' . htmlspecialchars($_GET['success']) . '</div>'; } ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Quiz ID</th>
                <th>Title</th>
                <th>Course</th>
                <th>Start Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="quiz-table-body">
            <?php if (empty($quizzes)): ?>
                <tr><td colspan="6" style="text-align:center;">You have not created any quizzes yet.</td></tr>
            <?php else: ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr id="quiz-row-<?php echo $quiz['id']; ?>">
                        <td><?php echo htmlspecialchars($quiz['id']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['course_name']); ?></td>
                        <td><?php echo date('M j, Y, g:i A', strtotime($quiz['start_time'])); ?></td>
                        <td>
                            <?php $status_class = strtolower(str_replace(' ', '_', $quiz['status_name'])); ?>
                            <span class="status-badge status-<?php echo htmlspecialchars($status_class); ?>"><?php echo htmlspecialchars($quiz['status_name']); ?></span>
                        </td>
                        <td class="action-buttons">
                            <a href="view_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-manage">Manage Questions</a>
                            <a href="start_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-start-quiz">Start Quiz</a>
                            <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-edit">Edit Details</a>
                            <a href="reports.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-reports">View Reports</a>
                            <a href="../shared/event_log_report.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-logs">View Logs</a>
                            <button class="btn-delete-quiz" data-quiz-id="<?php echo $quiz['id']; ?>">Delete Quiz</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('delete-quiz-modal');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    const quizTableBody = document.getElementById('quiz-table-body');
    let quizIdToDelete = null;

    // **FIX:** This event listener correctly handles clicks on any delete button in the table.
    quizTableBody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-delete-quiz')) {
            quizIdToDelete = e.target.dataset.quizId;
            modal.style.display = 'flex';
        }
    });

    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        quizIdToDelete = null;
    });

    confirmBtn.addEventListener('click', async () => {
        if (quizIdToDelete) {
            try {
                const response = await fetch('/nmims_quiz_app/api/faculty/delete_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ quiz_id: quizIdToDelete })
                });
                const result = await response.json();
                if (result.success) {
                    // Remove the quiz's row from the table on the screen
                    document.getElementById(`quiz-row-${quizIdToDelete}`).remove();
                } else {
                    throw new Error(result.error || 'Failed to delete quiz.');
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            } finally {
                modal.style.display = 'none';
                quizIdToDelete = null;
            }
        }
    });
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
