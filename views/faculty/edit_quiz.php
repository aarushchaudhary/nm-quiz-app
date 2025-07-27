<?php
  $pageTitle = 'Edit Quiz';
  $customCSS = 'manage.css'; 
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization & Input Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2 || !isset($_GET['id'])) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  $quiz_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
  $faculty_id = $_SESSION['user_id'];

  // --- Fetch the quiz data, including the school_id ---
  $stmt_quiz = $pdo->prepare("
      SELECT q.*, c.school_id 
      FROM quizzes q
      JOIN courses c ON q.course_id = c.id
      WHERE q.id = :quiz_id AND q.faculty_id = :faculty_id");
  $stmt_quiz->execute([':quiz_id' => $quiz_id, ':faculty_id' => $faculty_id]);
  $quiz = $stmt_quiz->fetch();

  if (!$quiz) {
      header('Location: manage_quizzes.php?error=not_found');
      exit();
  }

  // Fetch all schools for the dropdown
  $schools = $pdo->query("SELECT id, name FROM schools ORDER BY name ASC")->fetchAll();

  function format_datetime_for_input($datetime) {
      return date('Y-m-d\TH:i', strtotime($datetime));
  }
?>

<div class="form-container">
  <h2>Edit Quiz Details</h2>
  <form action="/nmims_quiz_app/api/faculty/update_quiz.php" method="POST">
    <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz['id']); ?>">
    
    <div class="form-group">
      <label for="title">Quiz Title</label>
      <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>" required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="school_id">School</label>
            <select id="school_id" name="school_id" required>
                <option value="" disabled>-- Select a School --</option>
                <?php foreach ($schools as $school): ?>
                <option value="<?php echo $school['id']; ?>" <?php if($school['id'] == $quiz['school_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($school['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="course_id">Course</label>
            <select id="course_id" name="course_id" required>
                <option value="">-- Loading... --</option>
            </select>
        </div>
        <div class="form-group">
            <label for="graduation_year">Graduation Year</label>
            <select id="graduation_year" name="graduation_year" required>
                <option value="">-- Loading... --</option>
            </select>
        </div>
    </div>

    <div class="form-row">
      <div class="form-group"><label>Start Time</label><input type="datetime-local" name="start_time" value="<?php echo format_datetime_for_input($quiz['start_time']); ?>" required></div>
      <div class="form-group"><label>End Time</label><input type="datetime-local" name="end_time" value="<?php echo format_datetime_for_input($quiz['end_time']); ?>" required></div>
      <div class="form-group"><label>Duration (Minutes)</label><input type="number" name="duration_minutes" value="<?php echo htmlspecialchars($quiz['duration_minutes']); ?>" min="1" required></div>
    </div>
    <hr style="margin: 25px 0;">
    <h3 style="text-align: center;">Question Configuration</h3>
    <div class="form-row">
      <div class="form-group"><label>Easy Questions</label><input type="number" name="config_easy_count" value="<?php echo htmlspecialchars($quiz['config_easy_count']); ?>" min="0" required></div>
      <div class="form-group"><label>Medium Questions</label><input type="number" name="config_medium_count" value="<?php echo htmlspecialchars($quiz['config_medium_count']); ?>" min="0" required></div>
      <div class="form-group"><label>Hard Questions</label><input type="number" name="config_hard_count" value="<?php echo htmlspecialchars($quiz['config_hard_count']); ?>" min="0" required></div>
    </div>
    <div class="form-group" style="text-align: center; margin-top: 30px;">
      <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Save Changes</button>
      <a href="manage_quizzes.php" style="display:inline-block; margin-left:15px; color:#555;">Cancel</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const schoolSelect = document.getElementById('school_id');
    const courseSelect = document.getElementById('course_id');
    const yearSelect = document.getElementById('graduation_year');

    // Store the pre-selected values from PHP
    const preselectedCourseId = <?php echo json_encode($quiz['course_id']); ?>;
    const preselectedYear = <?php echo json_encode($quiz['graduation_year']); ?>;

    async function populateCourses(schoolId, selectedCourseId = null) {
        courseSelect.innerHTML = '<option value="">Loading...</option>';
        courseSelect.disabled = true;
        const response = await fetch(`/nmims_quiz_app/api/shared/get_courses_by_school.php?school_id=${schoolId}`);
        const courses = await response.json();
        courseSelect.innerHTML = '<option value="" disabled>-- Select a Course --</option>';
        courses.forEach(course => {
            const option = new Option(course.name, course.id);
            if (course.id == selectedCourseId) {
                option.selected = true;
            }
            courseSelect.add(option);
        });
        courseSelect.disabled = false;
    }

    async function populateYears(courseId, selectedYear = null) {
        yearSelect.innerHTML = '<option value="">Loading...</option>';
        yearSelect.disabled = true;
        const response = await fetch(`/nmims_quiz_app/api/shared/get_years_by_course.php?course_id=${courseId}`);
        const years = await response.json();
        yearSelect.innerHTML = '<option value="" disabled>-- Select a Year --</option>';
        years.forEach(year => {
            const option = new Option(year, year);
            if (year == selectedYear) {
                option.selected = true;
            }
            yearSelect.add(option);
        });
        yearSelect.disabled = false;
    }

    // Event listeners for changes
    schoolSelect.addEventListener('change', function() {
        populateCourses(this.value);
        yearSelect.innerHTML = '<option value="">-- Select Course First --</option>';
        yearSelect.disabled = true;
    });

    courseSelect.addEventListener('change', function() {
        populateYears(this.value);
    });

    // Initial population on page load
    async function initializeDropdowns() {
        await populateCourses(schoolSelect.value, preselectedCourseId);
        await populateYears(courseSelect.value, preselectedYear);
    }
    
    initializeDropdowns();
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
