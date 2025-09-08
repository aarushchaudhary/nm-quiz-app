<?php
  $pageTitle = 'Upload Students';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
?>

<div class="manage-container">
    <a href="user_management.php" style="text-decoration: none; color: #007bff; margin-bottom: 20px; display: inline-block;">&larr; Back to User Management</a>
    <h2>Bulk Upload Students</h2>

    <?php
    if (isset($_GET['success'])) { echo '<div class="message-box success-message">' . htmlspecialchars($_GET['message']) . '</div>'; }
    if (isset($_GET['error'])) { echo '<div class="message-box error-message">' . htmlspecialchars($_GET['message']) . '</div>'; }
    
    if (isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])) {
        echo '<div class="message-box warning-message"><h4>Upload Warnings:</h4><ul>';
        foreach ($_SESSION['upload_errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
        unset($_SESSION['upload_errors']); // Clear errors after displaying
    }
    ?>
    
    <div class="section-box">
        <h3>Upload Excel File</h3>
        <div style="text-align:center; color: #555;">
            <p>Upload an Excel file (.xlsx) with student data. The file **must** contain the following headers in this order:</p>
            <p><code>full_name, sap_id, roll_no, school, course, graduation_year, batch, username, password, specializations</code></p>
            <p>
                The password field is optional (default: '<strong>Password123</strong>').<br>
                For the '<strong>specializations</strong>' column, enter names separated by a comma (e.g., "Marketing, Finance").
            </p>
        </div>

        <form action="/nmims_quiz_app/api/admin/upload_students.php" method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="student_file">Select Excel file to upload:</label>
                <input type="file" id="student_file" name="student_file" accept=".xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required style="padding: 10px; border: 1px solid #ccc; border-radius: 8px;">
            </div>
            <button type="submit" class="button-red">Upload and Create Students</button>
            
            <p style="text-align:center; margin-top:15px; font-size: 0.9em;">
                Need the format? <a href="/nmims_quiz_app/assets/templates/student_template.xlsx" download>Download New Excel Template</a>
            </p>
        </form>
    </div>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>