<?php
  // Set page-specific variables
  $pageTitle = 'Faculty Dashboard';
  
  // Include the header template
  // The path is relative to the file's location in the directory structure
  require_once '../../assets/templates/header.php';

  // --- Authorization Check ---
  // This is a critical security measure.
  // It ensures that only users with the 'faculty' role (role_id = 2) can access this page.
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
      // If the user is not a faculty member, redirect them to the login page
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  
  // Get the faculty's name from the session to display a personalized welcome message
  $facultyName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';
?>

<!-- The main content for the faculty dashboard -->
<div class="dashboard-center-content">
  <div class="welcome-message">
    Welcome, <?php echo $facultyName; ?>!
  </div>
  
  <!-- Button group for faculty actions -->
  <div class="button-group">
    <!-- Each button will eventually link to the corresponding feature page -->
    <a href="create_quiz.php" class="button-red">Create Quiz</a>
    <a href="manage_quizzes.php" class="button-red">Manage Quizzes</a>
    <a href="reports.php" class="button-red">View Results</a>
  </div>
</div>

<?php
  // Include the footer template to close the page
  require_once '../../assets/templates/footer.php';
?>
