<?php
  $pageTitle = 'Placement Dashboard';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';

  // --- Authorization Check for Placement Officer (role_id = 3) ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  
  $placecomName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Placement Officer';
?>

<div class="manage-container">
    <h2 style="margin-bottom: 10px;">Welcome, <?php echo $placecomName; ?>!</h2>
    <p style="text-align:center; color: #555; margin-top:0;">From here you can access reports for all quizzes conducted on the platform.</p>
    
    <!-- Grid for summary cards (can be implemented later like the admin dashboard) -->
    <div class="dashboard-grid">
        <!-- Cards can be added here -->
    </div>

    <!-- Links to other pages -->
    <div class="section-box" style="text-align:center;">
        <h3>Placement Tools</h3>
        <div class="button-group" style="justify-content:center;">
            <a href="reports.php" class="button-red" style="width:auto;">View All Quiz Reports</a>
            <a href="/nmims_quiz_app/views/shared/event_log_report.php" class="button-red" style="width:auto; background-color:#6c757d;">View Event Logs</a>
        </div>
    </div>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
