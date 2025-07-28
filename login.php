<?php
  // **FIX:** Added variables to set the page title and link the correct stylesheet.
  $pageTitle = 'Login';
  $customCSS = 'login.css'; 
  require_once 'assets/templates/header.php';
?>
<div class="form-container">
  <h2>User Login</h2>
  
  <div id="message-box" class="message-box error-message" style="display: none;"></div>

  <form id="login-form">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="button-red">Login</button>
  </form>
</div>

<script src="/nmims_quiz_app/assets/js/login.js" defer></script>

<?php
  require_once 'assets/templates/footer.php';
?>
