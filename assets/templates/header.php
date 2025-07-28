<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// **NEW:** Single Session Validation
// This block runs on every page to ensure the current session is the only active one.
if (isset($_SESSION['user_id'])) {
    // This check requires a database connection.
    require_once __DIR__ . '/../../config/database.php';

    $stmt = $pdo->prepare("SELECT active_session_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $active_session_id = $stmt->fetchColumn();

    // If the session ID in the database does not match the user's current session ID,
    // it means they have logged in from another location.
    // We destroy this older session, forcing them to log out.
    if ($active_session_id !== session_id()) {
        session_destroy();
        header('Location: /nmims_quiz_app/login.php?error=session_terminated');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'NMIMS Quiz App'; ?></title>
    
    <link rel="icon" type="image/png" href="/nmims_quiz_app/assets/images/favicon.jpg">
    
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
        
        <div class="header-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/nmims_quiz_app/index.php" class="home-button">Home</a>
                <a href="/nmims_quiz_app/logout.php" class="logout-button">Logout</a>
            <?php endif; ?>
        </div>
    </header>
    
    <main>
