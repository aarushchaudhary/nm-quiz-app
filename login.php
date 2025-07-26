<?php
  // Set the page title for the header
  $pageTitle = 'NMIMS - User Login';
  
  // Include the header template
  include 'assets/templates/header.php';

  // If a user is already logged in, redirect them away from the login page
  // The index.php file will handle routing them to the correct dashboard
  if (isset($_SESSION['user_id'])) {
      header('Location: index.php');
      exit();
  }
?>

<!-- The main login box, styled by components.css -->
<div class="login-box">
  <h2>Sign In</h2>
  
  <?php
    // Display an error message if the login failed
    if (isset($_GET['error'])) {
        $errorMsg = '';
        if ($_GET['error'] == 'invalid_credentials') {
            $errorMsg = 'Invalid username or password.';
        } elseif ($_GET['error'] == 'db_error') {
            $errorMsg = 'A database error occurred. Please try again later.';
        }
        echo '<p style="color: red; margin-bottom: 15px;">' . htmlspecialchars($errorMsg) . '</p>';
    }
  ?>

  <!-- The form submits to the authentication script -->
  <form action="api/auth.php" method="POST">
    <input class="input-field" type="text" name="username" placeholder="Username / SAP ID" required />
    <input class="input-field" type="password" name="password" placeholder="Password" required />
    <button class="button-red" type="submit">Login</button>
  </form>
</div>

<?php
  // Include the footer template to close the page
  include 'assets/templates/footer.php';
?>
