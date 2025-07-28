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

  // Fetch initial data for the form's dropdown menus
  $roles = $pdo->query("SELECT id, name FROM roles WHERE name != 'admin'")->fetchAll();
  $schools = $pdo->query("SELECT id, name FROM schools ORDER BY name ASC")->fetchAll();
?>

<div class="form-container" style="max-width: 800px;">
    <h2>Create New User Account</h2>
    <form action="/nmims_quiz_app/api/admin/add_user.php" method="POST" id="add-user-form">
        
        <div class="form-group">
            <label for="role_id">User Role</label>
            <select id="role_id" name="role_id" class="input-field" required>
                <option value="" disabled selected>-- Select a Role --</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name" class="input-field" required></div>
            <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" class="input-field" required></div>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="input-field" required>
        </div>
        
        <hr>

        <div id="role-specific-fields">

            <div class="student-fields" style="display:none;">
                <h4>Student Details</h4>
                <div class="form-row">
                    <div class="form-group"><label>SAP ID</label><input type="text" name="sap_id" class="input-field"></div>
                    <div class="form-group"><label>Roll No.</label><input type="text" name="roll_no" class="input-field"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_school_id">School</label>
                        <select id="student_school_id" name="school_id" class="input-field">
                            <option value="">-- Select a School --</option>
                            <?php foreach($schools as $school): ?>
                            <option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id" class="input-field" disabled><option value="">-- Select School First --</option></select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="grad_year">Graduation Year</label>
                        <input type="number" id="grad_year" name="graduation_year" class="input-field" placeholder="e.g., 2026">
                    </div>
                     <div class="form-group">
                        <label for="batch">Batch</label>
                        <input type="text" id="batch" name="batch" class="input-field" placeholder="e.g., 2024-2028">
                    </div>
                </div>
            </div>

            <div class="staff-fields" style="display:none;">
                <h4>Staff Details</h4>
                <div class="form-row">
                    <div class="form-group" id="staff-sap-id-group"><label>SAP ID</label><input type="text" name="staff_sap_id" class="input-field"></div>
                    <div class="form-group" id="staff-school-group"><label>School / Department</label><select id="staff_school_id" name="staff_school_id" class="input-field"><option value="">-- Select a School / Department --</option><?php foreach($schools as $school): ?><option value="<?php echo $school['id']; ?>"><?php echo htmlspecialchars($school['name']); ?></option><?php endforeach; ?></select></div>
                </div>
            </div>

        </div>

        <div class="form-group" style="text-align: center; margin-top: 30px;">
            <button type="submit" class="button-red" style="width: auto; padding: 12px 40px;">Create User</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role_id');
    const studentFields = document.querySelector('.student-fields');
    const staffFields = document.querySelector('.staff-fields');
    const staffSapIdGroup = document.getElementById('staff-sap-id-group');
    const staffSchoolGroup = document.getElementById('staff-school-group');
    
    // **FIX:** The JavaScript logic is now more specific for each role.
    roleSelect.addEventListener('change', function() {
        const roleId = this.value;
        // Hide all optional sections first
        studentFields.style.display = 'none';
        staffFields.style.display = 'none';
        staffSapIdGroup.style.display = 'none';
        staffSchoolGroup.style.display = 'none';

        if (roleId === '4') { // Student
            studentFields.style.display = 'block';
        } else if (roleId === '2') { // Faculty
            staffFields.style.display = 'block';
            staffSapIdGroup.style.display = 'block';
            staffSchoolGroup.style.display = 'block';
        } else if (roleId === '3') { // Placement Officer
            staffFields.style.display = 'block';
            staffSapIdGroup.style.display = 'block';
        }
        // For any other role (like Heads), no extra fields will be shown.
    });

    // --- Cascading Dropdown Logic for Students (unchanged) ---
    const schoolSelect = document.getElementById('student_school_id');
    const courseSelect = document.getElementById('course_id');
    const batchInput = document.getElementById('batch'); // Assuming text input

    schoolSelect.addEventListener('change', async function() {
        const schoolId = this.value;
        courseSelect.innerHTML = '<option value="">Loading...</option>';
        courseSelect.disabled = true;

        if (!schoolId) {
            courseSelect.innerHTML = '<option value="">-- Select School First --</option>';
            return;
        }

        try {
            const response = await fetch(`/nmims_quiz_app/api/shared/get_courses_by_school.php?school_id=${schoolId}`);
            const courses = await response.json();
            
            courseSelect.innerHTML = '<option value="" disabled selected>-- Select a Course --</option>';
            courses.forEach(course => {
                courseSelect.add(new Option(course.name, course.id));
            });
            courseSelect.disabled = false;
        } catch (error) {
            console.error('Failed to load courses:', error);
            courseSelect.innerHTML = '<option value="">Error loading</option>';
        }
    });
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
