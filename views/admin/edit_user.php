<?php
  $pageTitle = 'Edit User';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization & Input Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1 || !isset($_GET['id'])) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

  // --- Fetch all user data ---
  $sql = "SELECT u.id, u.username, u.role_id, s.*, f.*, p.*
          FROM users u
          LEFT JOIN students s ON u.id = s.user_id
          LEFT JOIN faculties f ON u.id = f.user_id
          LEFT JOIN placement_officers p ON u.id = p.user_id
          WHERE u.id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
      header('Location: user_management.php?error=user_not_found');
      exit();
  }
  
  // Determine the full name from the correct table
  $full_name = $user['name'] ?? ''; // This will get the name from students, faculties, or placecom

  $courses = $pdo->query("SELECT id, name FROM courses")->fetchAll();
?>

<div class="form-container" style="max-width: 800px;">
    <h2>Edit User: <?php echo htmlspecialchars($full_name); ?></h2>
    <form action="/nmims_quiz_app/api/admin/update_user.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
        <input type="hidden" name="role_id" value="<?php echo $user['role_id']; ?>">
        
        <div class="form-row">
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required></div>
            <div class="form-group"><label>Username</label><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
        </div>
        <div class="form-group">
            <label>New Password (leave blank to keep current password)</label>
            <input type="password" name="password">
        </div>
        <hr>

        <!-- Student Fields -->
        <?php if ($user['role_id'] == 4): ?>
        <div class="student-fields">
            <h4>Student Details</h4>
            <div class="form-row">
                <div class="form-group"><label>SAP ID</label><input type="text" name="sap_id" value="<?php echo htmlspecialchars($user['sap_id']); ?>"></div>
                <div class="form-group"><label>Roll No.</label><input type="text" name="roll_no" value="<?php echo htmlspecialchars($user['roll_no']); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Course</label><select name="course_id"><?php foreach($courses as $c) echo "<option value='{$c['id']}' ".($user['course_id']==$c['id']?'selected':'').">{$c['name']}</option>"; ?></select></div>
                <div class="form-group"><label>Batch</label><input type="text" name="batch" value="<?php echo htmlspecialchars($user['batch']); ?>"></div>
                <div class="form-group"><label>Graduation Year</label><input type="number" name="graduation_year" value="<?php echo htmlspecialchars($user['graduation_year']); ?>"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Faculty/Placecom Fields -->
        <?php if ($user['role_id'] == 2 || $user['role_id'] == 3): ?>
        <div class="faculty-fields">
            <h4>Details</h4>
            <div class="form-row">
                <div class="form-group"><label>SAP ID</label><input type="text" name="faculty_sap_id" value="<?php echo htmlspecialchars($user['sap_id']); ?>"></div>
                <div class="form-group"><label>Department</label><input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>"></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group" style="text-align: center; margin-top: 30px;">
            <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Save Changes</button>
        </div>
    </form>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
