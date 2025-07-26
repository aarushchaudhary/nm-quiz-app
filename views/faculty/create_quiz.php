<?php
  $pageTitle = 'Create New Quiz';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // --- Fetch Courses from Database ---
  // This query retrieves all available courses to populate the dropdown select menu.
  try {
      $courses_stmt = $pdo->query("SELECT id, name, code FROM courses ORDER BY name ASC");
      $courses = $courses_stmt->fetchAll();
  } catch (PDOException $e) {
      // Handle potential database error
      error_log("Failed to fetch courses: " . $e->getMessage());
      $courses = []; // Ensure $courses is an array to prevent errors
  }
?>

<div class="form-container">
  <h2>Quiz Setup</h2>

  <!-- The form submits data to the backend script for processing -->
  <form action="/nmims_quiz_app/api/faculty/create_quiz.php" method="POST">
    
    <div class="form-group">
      <label for="title">Quiz Title</label>
      <input type="text" id="title" name="title" placeholder="e.g., Data Structures - Mid Term Exam" required>
    </div>

    <div class="form-group">
      <label for="course_id">Course</label>
      <select id="course_id" name="course_id" required>
        <option value="" disabled selected>Select a course</option>
        <?php foreach ($courses as $course): ?>
          <option value="<?php echo htmlspecialchars($course['id']); ?>">
            <?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['code']) . ')'; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="start_time">Start Time</label>
        <input type="datetime-local" id="start_time" name="start_time" required>
      </div>
      <div class="form-group">
        <label for="end_time">End Time</label>
        <input type="datetime-local" id="end_time" name="end_time" required>
      </div>
      <div class="form-group">
        <label for="duration">Duration (Minutes)</label>
        <input type="number" id="duration" name="duration_minutes" min="1" placeholder="e.g., 60" required>
      </div>
    </div>

    <hr style="margin: 25px 0;">

    <h3 style="text-align: center; margin-bottom: 20px;">Question Configuration</h3>
    <p style="text-align: center; margin-top: -15px; margin-bottom: 20px; color: #666;">Define the number of questions to be randomly selected for each difficulty.</p>
    
    <div class="form-row">
      <div class="form-group">
        <label for="easy_count">Easy Questions</label>
        <input type="number" id="easy_count" name="config_easy_count" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label for="medium_count">Medium Questions</label>
        <input type="number" id="medium_count" name="config_medium_count" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label for="hard_count">Hard Questions</label>
        <input type="number" id="hard_count" name="config_hard_count" min="0" value="0" required>
      </div>
    </div>

    <div class="form-group" style="text-align: center; margin-top: 30px;">
      <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Create Quiz</button>
    </div>

  </form>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
