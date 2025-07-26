<?php
  $pageTitle = 'Edit Quiz';
  // We can reuse the form styles from the create quiz page
  $customCSS = 'manage.css'; 
  
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization & Input Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
      header('Location: manage_quizzes.php');
      exit();
  }

  $quiz_id = $_GET['id'];
  $faculty_id = $_SESSION['user_id'];

  // --- Fetch the quiz to be edited, ensuring it belongs to the logged-in faculty ---
  $stmt_quiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = :quiz_id AND faculty_id = :faculty_id");
  $stmt_quiz->execute([':quiz_id' => $quiz_id, ':faculty_id' => $faculty_id]);
  $quiz = $stmt_quiz->fetch();

  if (!$quiz) {
      // If the quiz doesn't exist or doesn't belong to this faculty, redirect them.
      header('Location: manage_quizzes.php?error=not_found');
      exit();
  }

  // --- Fetch all available courses for the dropdown menu ---
  $courses_stmt = $pdo->query("SELECT id, name, code FROM courses ORDER BY name ASC");
  $courses = $courses_stmt->fetchAll();

  // Helper function to format datetime for the input field
  function format_datetime_for_input($datetime) {
      return date('Y-m-d\TH:i', strtotime($datetime));
  }
?>

<div class="form-container">
  <h2>Edit Quiz Details</h2>

  <!-- The form submits to the update script -->
  <form action="/nmims_quiz_app/api/faculty/update_quiz.php" method="POST">
    <!-- Hidden input to identify which quiz to update -->
    <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz['id']); ?>">
    
    <div class="form-group">
      <label for="title">Quiz Title</label>
      <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>" required>
    </div>

    <div class="form-group">
      <label for="course_id">Course</label>
      <select id="course_id" name="course_id" required>
        <option value="" disabled>Select a course</option>
        <?php foreach ($courses as $course): ?>
          <option value="<?php echo htmlspecialchars($course['id']); ?>" <?php echo ($course['id'] == $quiz['course_id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['code']) . ')'; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="start_time">Start Time</label>
        <input type="datetime-local" id="start_time" name="start_time" value="<?php echo format_datetime_for_input($quiz['start_time']); ?>" required>
      </div>
      <div class="form-group">
        <label for="end_time">End Time</label>
        <input type="datetime-local" id="end_time" name="end_time" value="<?php echo format_datetime_for_input($quiz['end_time']); ?>" required>
      </div>
      <div class="form-group">
        <label for="duration">Duration (Minutes)</label>
        <input type="number" id="duration" name="duration_minutes" value="<?php echo htmlspecialchars($quiz['duration_minutes']); ?>" min="1" required>
      </div>
    </div>

    <hr style="margin: 25px 0;">

    <h3 style="text-align: center; margin-bottom: 20px;">Question Configuration</h3>
    
    <div class="form-row">
      <div class="form-group">
        <label for="easy_count">Easy Questions</label>
        <input type="number" id="easy_count" name="config_easy_count" value="<?php echo htmlspecialchars($quiz['config_easy_count']); ?>" min="0" required>
      </div>
      <div class="form-group">
        <label for="medium_count">Medium Questions</label>
        <input type="number" id="medium_count" name="config_medium_count" value="<?php echo htmlspecialchars($quiz['config_medium_count']); ?>" min="0" required>
      </div>
      <div class="form-group">
        <label for="hard_count">Hard Questions</label>
        <input type="number" id="hard_count" name="config_hard_count" value="<?php echo htmlspecialchars($quiz['config_hard_count']); ?>" min="0" required>
      </div>
    </div>

    <div class="form-group" style="text-align: center; margin-top: 30px;">
      <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Save Changes</button>
      <a href="manage_quizzes.php" style="display:inline-block; margin-left:15px; color:#555;">Cancel</a>
    </div>

  </form>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
