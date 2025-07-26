<?php
  $pageTitle = 'Admin Dashboard';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';

  // --- Authorization Check for Admin (role_id = 1) ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  
  $adminName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin';
?>

<div class="manage-container">
    <h2 style="margin-bottom: 10px;">Welcome, <?php echo $adminName; ?>!</h2>
    <p style="text-align:center; color: #555; margin-top:0;">Here's a summary of the application activity.</p>
    
    <!-- Grid for summary cards -->
    <div class="dashboard-grid">
        <!-- Total Students Card -->
        <div class="dashboard-card card-students">
            <div class="card-icon">🎓</div>
            <div class="card-info">
                <p class="card-title">Total Students</p>
                <p class="card-number" id="student-count">0</p>
            </div>
        </div>
        <!-- Total Faculty Card -->
        <div class="dashboard-card card-faculty">
            <div class="card-icon">🧑‍🏫</div>
            <div class="card-info">
                <p class="card-title">Total Faculty</p>
                <p class="card-number" id="faculty-count">0</p>
            </div>
        </div>
        <!-- Total Quizzes Card -->
        <div class="dashboard-card card-quizzes">
            <div class="card-icon">📝</div>
            <div class="card-info">
                <p class="card-title">Total Quizzes</p>
                <p class="card-number" id="quiz-count">0</p>
            </div>
        </div>
        <!-- Active Quizzes Card -->
        <div class="dashboard-card card-active">
            <div class="card-icon">🔴</div>
            <div class="card-info">
                <p class="card-title">Active Quizzes</p>
                <p class="card-number" id="active-quiz-count">0</p>
            </div>
        </div>
    </div>

    <!-- Links to other admin pages -->
    <div class="section-box" style="text-align:center;">
        <h3>Admin Tools</h3>
        <div class="button-group" style="justify-content:center;">
            <a href="user_management.php" class="button-red" style="width:auto;">Manage Users</a>
            <a href="#" class="button-red" style="width:auto; background-color:#6c757d;">View System Logs</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch('/nmims_quiz_app/api/admin/get_dashboard_stats.php');
        if (!response.ok) throw new Error('Failed to fetch stats.');
        
        const stats = await response.json();
        
        document.getElementById('student-count').textContent = stats.students || 0;
        document.getElementById('faculty-count').textContent = stats.faculty || 0;
        document.getElementById('quiz-count').textContent = stats.quizzes || 0;
        document.getElementById('active-quiz-count').textContent = stats.active_quizzes || 0;

    } catch (error) {
        console.error("Error loading dashboard stats:", error);
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
