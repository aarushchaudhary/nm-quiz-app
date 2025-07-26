<?php
  $pageTitle = 'Add New User';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // Fetch roles and courses for the dropdowns
  $roles = $pdo->query("SELECT id, name FROM roles WHERE name != 'admin'")->fetchAll();
  $courses = $pdo->query("SELECT id, name FROM courses")->fetchAll();
?>

<div class="form-container" style="max-width: 800px;">
    <h2>Create New User Account</h2>
    <form action="/nmims_quiz_app/api/admin/add_user.php" method="POST" id="add-user-form">
        
        <div class="form-group">
            <label for="role_id">User Role</label>
            <select id="role_id" name="role_id" required>
                <option value="" disabled selected>-- Select a Role --</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <hr>

        <!-- Role-specific fields will be shown/hidden here -->
        <div id="role-specific-fields">
            <!-- Student Fields (role_id = 4) -->
            <div class="student-fields" style="display:none;">
                <div class="form-row">
                    <div class="form-group"><label for="sap_id">SAP ID</label><input type="text" id="sap_id" name="sap_id"></div>
                    <div class="form-group"><label for="roll_no">Roll No.</label><input type="text" id="roll_no" name="roll_no"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="course_id">Course</label><select id="course_id" name="course_id"><?php foreach($courses as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?></select></div>
                    <div class="form-group"><label for="batch">Batch</label><input type="text" id="batch" name="batch"></div>
                    <div class="form-group"><label for="grad_year">Graduation Year</label><input type="number" id="grad_year" name="graduation_year"></div>
                </div>
            </div>

            <!-- Faculty/Placecom Fields (role_id = 2 or 3) -->
            <div class="faculty-fields" style="display:none;">
                <div class="form-row">
                    <div class="form-group"><label for="faculty_sap_id">SAP ID</label><input type="text" id="faculty_sap_id" name="faculty_sap_id"></div>
                    <div class="form-group"><label for="department">Department</label><input type="text" id="department" name="department"></div>
                </div>
            </div>
        </div>

        <div class="form-group" style="text-align: center; margin-top: 30px;">
            <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Create User</button>
        </div>
    </form>
</div>

<script>
document.getElementById('role_id').addEventListener('change', function() {
    const roleId = this.value;
    // Hide all role-specific sections first
    document.querySelector('.student-fields').style.display = 'none';
    document.querySelector('.faculty-fields').style.display = 'none';

    if (roleId === '4') { // Student
        document.querySelector('.student-fields').style.display = 'block';
    } else if (roleId === '2' || roleId === '3') { // Faculty or Placecom
        document.querySelector('.faculty-fields').style.display = 'block';
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
