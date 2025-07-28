<?php
/*
 * auth.php
 * Handles user authentication.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /nmims_quiz_app/login.php');
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];

// **MODIFIED:** The query now joins with the 'roles' table to get the role name,
// and includes the 'heads' table in the COALESCE to get the full name for new roles.
$sql = "
    SELECT 
        u.id, 
        u.password_hash, 
        u.role_id,
        r.name as role_name,
        COALESCE(s.name, f.name, p.name, a.name, h.name) as full_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN students s ON u.id = s.user_id AND u.role_id = 4
    LEFT JOIN faculties f ON u.id = f.user_id AND u.role_id = 2
    LEFT JOIN placement_officers p ON u.id = p.user_id AND u.role_id = 3
    LEFT JOIN admins a ON u.id = a.user_id AND u.role_id = 1
    LEFT JOIN heads h ON u.id = h.user_id
    WHERE u.username = :username
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role_name'] = $user['role_name']; // **NEW:** Store role name in session
    
    header('Location: /nmims_quiz_app/index.php');
    exit();
} else {
    header('Location: /nmims_quiz_app/login.php?error=invalid_credentials');
    exit();
}
