<?php
  $pageTitle = 'Manage Quiz';
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

  // --- Fetch Quiz Details ---
  $sql = "SELECT q.*, c.name as course_name, es.name as status_name 
          FROM quizzes q 
          JOIN courses c ON q.course_id = c.id
          JOIN exam_statuses es ON q.status_id = es.id
          WHERE q.id = :quiz_id AND q.faculty_id = :faculty_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':quiz_id' => $quiz_id, ':faculty_id' => $faculty_id]);
  $quiz = $stmt->fetch();

  if (!$quiz) {
      header('Location: manage_quizzes.php');
      exit();
  }
  
  // --- Fetch Data for Forms ---
  $questions_sql = "SELECT q.id, q.question_text, qt.name as type_name, qd.level as difficulty_level 
                    FROM questions q
                    JOIN question_types qt ON q.question_type_id = qt.id
                    JOIN question_difficulties qd ON q.difficulty_id = qd.id
                    WHERE quiz_id = :quiz_id ORDER BY q.id DESC";
  $questions_stmt = $pdo->prepare($questions_sql);
  $questions_stmt->execute([':quiz_id' => $quiz_id]);
  $questions = $questions_stmt->fetchAll();

  $question_types = $pdo->query("SELECT id, name FROM question_types")->fetchAll();
  $difficulties = $pdo->query("SELECT id, level FROM question_difficulties")->fetchAll();
?>

<div class="manage-container">
    <a href="manage_quizzes.php" style="text-decoration: none; color: #007bff; margin-bottom: 20px; display: inline-block;">&larr; Back to My Quizzes</a>
    <h2 style="text-align: center;"><?php echo htmlspecialchars($quiz['title']); ?></h2>
    
    <?php
    if (isset($_GET['success'])) { echo '<div class="message-box success-message">' . htmlspecialchars($_GET['success']) . '</div>'; }
    if (isset($_GET['error'])) { echo '<div class="message-box error-message">' . htmlspecialchars($_GET['error']) . '</div>'; }
    ?>

    <!-- Details Grid -->
    <div class="quiz-details-grid">
        <div class="detail-item"><strong>Course</strong><span><?php echo htmlspecialchars($quiz['course_name']); ?></span></div>
        <div class="detail-item"><strong>Start Time</strong><span><?php echo date('M j, Y, g:i A', strtotime($quiz['start_time'])); ?></span></div>
        <div class="detail-item"><strong>End Time</strong><span><?php echo date('M j, Y, g:i A', strtotime($quiz['end_time'])); ?></span></div>
        <div class="detail-item"><strong>Duration</strong><span><?php echo htmlspecialchars($quiz['duration_minutes']); ?> mins</span></div>
        <div class="detail-item">
            <strong>Status</strong>
            <?php $status_class = strtolower(str_replace(' ', '_', $quiz['status_name'])); ?>
            <span class="status-badge status-<?php echo htmlspecialchars($status_class); ?>">
                <?php echo htmlspecialchars($quiz['status_name']); ?>
            </span>
        </div>
        <div class="detail-item"><strong>Total Questions</strong><span><?php echo count($questions); ?></span></div>
    </div>

    <!-- Manual Add Question Section -->
    <div class="section-box">
        <h3>Add Question Manually</h3>
        <form action="/nmims_quiz_app/api/faculty/add_manual_question.php" method="POST" class="manual-add-form">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
            
            <div class="form-group">
                <label for="question_text">Question Text</label>
                <textarea id="question_text" name="question_text" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="question_type_id">Question Type</label>
                    <select id="question_type_id" name="question_type_id" required>
                        <option value="" disabled selected>-- Select Type --</option>
                        <?php foreach($question_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="difficulty_id">Difficulty</label>
                    <select id="difficulty_id" name="difficulty_id" required>
                        <option value="" disabled selected>-- Select Difficulty --</option>
                        <?php foreach($difficulties as $diff): ?>
                        <option value="<?php echo $diff['id']; ?>"><?php echo htmlspecialchars($diff['level']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="options-section" style="display: none;">
                <div class="options-container">
                    <p style="margin-top:0; font-weight:bold; text-align:center;">Options & Correct Answer</p>
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="option-item">
                        <label>Option <?php echo $i + 1; ?>:</label>
                        <input type="text" name="options[]" class="input-field">
                        <div class="correct-answer-group">
                           <input type="checkbox" name="correct_answers[]" value="<?php echo $i; ?>">
                           <label>Correct</label>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group" style="text-align: center; margin-top: 30px;">
                <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Add Question</button>
            </div>
        </form>
    </div>

    <!-- Other sections here -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionTypeSelect = document.getElementById('question_type_id');
    const optionsSection = document.getElementById('options-section');
    const correctInputs = document.querySelectorAll('input[name="correct_answers[]"]');

    function toggleOptions() {
        const selectedType = questionTypeSelect.value;
        
        if (selectedType === '3' || selectedType === '') { // Descriptive or not selected
            optionsSection.style.display = 'none';
        } else {
            optionsSection.style.display = 'block';
        }

        // **FIX:** Change input type based on selection
        correctInputs.forEach(input => {
            if (selectedType === '1') { // MCQ
                input.type = 'radio';
                // Radio buttons in a group MUST share the same name
                input.name = 'correct_answer_single'; 
            } else { // Multiple Answer
                input.type = 'checkbox';
                input.name = 'correct_answers[]';
            }
        });
    }

    questionTypeSelect.addEventListener('change', toggleOptions);
    // Run on page load to set initial state
    toggleOptions();
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
