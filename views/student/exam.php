<?php
  $pageTitle = 'Taking Exam';
  $customCSS = 'exam.css';
  require_once '../../assets/templates/header.php';

  $quiz_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
?>

<!-- Proctoring Warning Modal -->
<div id="warning-overlay">
    <div class="warning-box">
        <h2>Warning!</h2>
        <p>You have left the exam window. This action has been logged. Please remain on this page to avoid disqualification.</p>
        <p>You have <span id="warning-count" class="warning-count">1 of 2</span> warnings.</p>
    </div>
</div>

<!-- Main Exam UI -->
<div id="exam-container" class="exam-container" style="display:none;" data-quiz-id="<?php echo htmlspecialchars($quiz_id); ?>">
    <div class="exam-header">
        <div class="question-counter" id="question-counter">Question 1 of N</div>
        <div class="timer" id="timer">Time Left: 00:00</div>
    </div>
    <div class="question-area">
        <p class="question-text" id="question-text">Loading question...</p>
        <div class="options-grid" id="options-grid"></div>
    </div>
    <div class="exam-footer">
        <button class="btn-next" id="next-btn" disabled>Next Question</button>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay">
    <div class="spinner"></div>
    <p style="text-align:center; font-weight:bold;">Preparing your exam...</p>
</div>

<!-- Include the external JavaScript file -->
<script src="/nmims_quiz_app/assets/js/exam_logic.js" defer></script>
