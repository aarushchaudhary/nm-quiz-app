<?php
  $pageTitle = 'Create New Quiz';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // --- Fetch Schools for the first dropdown ---
  $schools = $pdo->query("SELECT id, name FROM schools ORDER BY name ASC")->fetchAll();
?>

<div class="form-container">
  <h2>Quiz Setup</h2>
  <form action="/nmims_quiz_app/api/faculty/create_quiz.php" method="POST">
    
    <div class="form-group">
      <label for="title">Quiz Title</label>
      <input type="text" id="title" name="title" placeholder="e.g., Data Structures - Mid Term Exam" required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="school_id">School</label>
            <select id="school_id" name="school_id" required>
                <option value="" disabled selected>-- Select a School --</option>
                <?php foreach ($schools as $school): ?>
                <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="course_id">Course</label>
            <select id="course_id" name="course_id" required disabled>
                <option value="">-- Select School First --</option>
            </select>
        </div>
        <div class="form-group">
            <label for="graduation_year">Graduation Year / Batch</label>
            <select id="graduation_year" name="graduation_year" required disabled>
                <option value="">-- Select Course First --</option>
            </select>
        </div>
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
    <h3 style="text-align: center; margin-bottom: 20px;">Student & Question Configuration</h3>
    
    <div class="form-row">
        <div class="form-group">
            <label for="sap_id_start">SAP ID Range (Optional)</label>
            <input type="text" name="sap_id_range_start" id="sap_id_start" class="input-field" placeholder="Starting SAP ID">
        </div>
        <div class="form-group">
            <label for="sap_id_end">&nbsp;</label> <input type="text" name="sap_id_range_end" id="sap_id_end" class="input-field" placeholder="Ending SAP ID">
        </div>
    </div>

    <div class="form-row">
      <div class="form-group"><label for="easy_count">Easy Questions</label><input type="number" id="easy_count" name="config_easy_count" min="0" value="0" required></div>
      <div class="form-group"><label for="medium_count">Medium Questions</label><input type="number" id="medium_count" name="config_medium_count" min="0" value="0" required></div>
      <div class="form-group"><label for="hard_count">Hard Questions</label><input type="number" id="hard_count" name="config_hard_count" min="0" value="0" required></div>
    </div>
    <div class="form-group" style="text-align: center; margin-top: 30px;">
      <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Create Quiz</button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const schoolSelect = document.getElementById('school_id');
    const courseSelect = document.getElementById('course_id');
    const yearSelect = document.getElementById('graduation_year');

    schoolSelect.addEventListener('change', async function() {
        const schoolId = this.value;
        courseSelect.innerHTML = '<option value="">Loading...</option>';
        courseSelect.disabled = true;
        yearSelect.innerHTML = '<option value="">-- Select Course First --</option>';
        yearSelect.disabled = true;

        if (!schoolId) return;

        const response = await fetch(`/nmims_quiz_app/api/shared/get_courses_by_school.php?school_id=${schoolId}`);
        const courses = await response.json();
        
        courseSelect.innerHTML = '<option value="" disabled selected>-- Select a Course --</option>';
        courses.forEach(course => {
            const option = new Option(course.name, course.id);
            courseSelect.add(option);
        });
        courseSelect.disabled = false;
    });

    courseSelect.addEventListener('change', async function() {
        const courseId = this.value;
        yearSelect.innerHTML = '<option value="">Loading...</option>';
        yearSelect.disabled = true;

        if (!courseId) return;

        const response = await fetch(`/nmims_quiz_app/api/shared/get_years_by_course.php?course_id=${courseId}`);
        const years = await response.json();
        
        yearSelect.innerHTML = '<option value="" disabled selected>-- Select a Year --</option>';
        years.forEach(year => {
            const option = new Option(year, year);
            yearSelect.add(option);
        });
        yearSelect.disabled = false;
    });
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
