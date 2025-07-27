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
    <div class="dashboard-grid" id="dashboard-grid">
        <!-- Cards are loaded by JavaScript -->
        <div class="dashboard-card card-students"><div class="card-icon">🎓</div><div class="card-info"><p class="card-title">Total Students</p><p class="card-number">0</p></div></div>
        <div class="dashboard-card card-faculty"><div class="card-icon">🧑‍🏫</div><div class="card-info"><p class="card-title">Total Faculty</p><p class="card-number">0</p></div></div>
        <div class="dashboard-card card-quizzes"><div class="card-icon">📝</div><div class="card-info"><p class="card-title">Total Quizzes</p><p class="card-number">0</p></div></div>
        <div class="dashboard-card card-active"><div class="card-icon">🔴</div><div class="card-info"><p class="card-title">Active Quizzes</p><p class="card-number">0</p></div></div>
    </div>

    <!-- Links to other admin pages -->
    <div class="section-box" style="text-align:center;">
        <h3>Admin Tools</h3>
        <div class="button-group" style="justify-content:center;">
            <a href="user_management.php" class="button-red" style="width:auto;">Manage Users</a>
            <a href="manage_schools.php" class="button-red" style="width:auto; background-color:#17a2b8;">Manage Schools</a>
            <a href="manage_courses.php" class="button-red" style="width:auto; background-color:#ffc107; color:#333;">Manage Courses</a>
            <a href="system_logs.php" class="button-red" style="width:auto; background-color:#6c757d;">View System Logs</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch('/nmims_quiz_app/api/admin/get_dashboard_stats.php');
        if (!response.ok) throw new Error('Failed to fetch stats.');
        
        const stats = await response.json();
        
        const grid = document.getElementById('dashboard-grid');
        // This replaces the placeholder cards with cards containing real data
        grid.innerHTML = `
            <div class="dashboard-card card-students"><div class="card-icon">🎓</div><div class="card-info"><p class="card-title">Total Students</p><p class="card-number">${stats.students || 0}</p></div></div>
            <div class="dashboard-card card-faculty"><div class="card-icon">🧑‍🏫</div><div class="card-info"><p class="card-title">Total Faculty</p><p class="card-number">${stats.faculty || 0}</p></div></div>
            <div class="dashboard-card card-quizzes"><div class="card-icon">📝</div><div class="card-info"><p class="card-title">Total Quizzes</p><p class="card-number">${stats.quizzes || 0}</p></div></div>
            <div class="dashboard-card card-active"><div class="card-icon">🔴</div><div class="card-info"><p class="card-title">Active Quizzes</p><p class="card-number">${stats.active_quizzes || 0}</p></div></div>
        `;

    } catch (error) {
        console.error("Error loading dashboard stats:", error);
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
