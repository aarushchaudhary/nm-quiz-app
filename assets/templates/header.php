<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'NMIMS Quiz App'; ?></title>
    
    <link rel="stylesheet" href="/nmims_quiz_app/assets/css/base.css" />
    <link rel="stylesheet" href="/nmims_quiz_app/assets/css/components.css" />
    <?php if (isset($customCSS)): ?>
        <link rel="stylesheet" href="/nmims_quiz_app/assets/css/<?php echo htmlspecialchars($customCSS); ?>" />
    <?php endif; ?>
</head>
<body>
    <header class="ribbon">
        <img src="/nmims_quiz_app/assets/images/logostme.png" alt="Logo" class="logo" />
        <h1 class="site-title"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'NMIMS Quiz App'; ?></h1>
        
        <!-- **FIX:** Logout button added here -->
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/nmims_quiz_app/logout.php" class="logout-button">Logout</a>
            <?php endif; ?>
        </div>
    </header>
    
    <main>
