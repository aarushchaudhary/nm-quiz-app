<?php
  $pageTitle = 'User Management';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check for Admin (role_id = 1) ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // --- Fetch all users with their roles and names ---
  $sql = "SELECT
            u.id,
            u.username,
            r.name as role_name,
            COALESCE(s.name, f.name, p.name, a.name) as full_name
          FROM
            users u
          JOIN
            roles r ON u.role_id = r.id
          LEFT JOIN students s ON u.id = s.user_id AND u.role_id = 4
          LEFT JOIN faculties f ON u.id = f.user_id AND u.role_id = 2
          LEFT JOIN placement_officers p ON u.id = p.user_id AND u.role_id = 3
          LEFT JOIN admins a ON u.id = a.user_id AND u.role_id = 1
          ORDER BY u.id DESC";
  
  $stmt = $pdo->query($sql);
  $users = $stmt->fetchAll();
?>

<!-- Confirmation Modal for Deletion -->
<div class="confirm-modal-overlay" id="delete-confirm-modal">
    <div class="confirm-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to permanently delete this user? This action cannot be undone.</p>
        <div class="button-group">
            <button class="btn-cancel" id="cancel-delete-btn">Cancel</button>
            <button class="btn-confirm-delete" id="confirm-delete-btn">Delete</button>
        </div>
    </div>
</div>


<div class="manage-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>All Users (<?php echo count($users); ?>)</h2>
        <a href="add_user.php" class="button-red" style="width: auto; padding: 10px 20px;">+ Add New User</a>
    </div>

    <?php
    if (isset($_GET['success'])) { echo '<div class="message-box success-message">' . htmlspecialchars($_GET['success']) . '</div>'; }
    if (isset($_GET['error'])) { echo '<div class="message-box error-message">' . htmlspecialchars($_GET['error']) . '</div>'; }
    ?>

    <table class="data-table">
        <thead>
            <tr><th>Full Name</th><th>Username / SAP ID</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr id="user-row-<?php echo $user['id']; ?>">
                    <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($user['role_name'])); ?></td>
                    <td class="action-buttons">
                        <!-- **FIX:** The href attribute is now correctly pointing to the edit page -->
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-edit">Edit</a>
                        <button class="btn-delete" data-user-id="<?php echo $user['id']; ?>" style="background-color:#dc3545; color:white; border:none; padding: 8px 12px; font-size: 14px; border-radius: 6px; cursor:pointer;">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('delete-confirm-modal');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    let userIdToDelete = null;

    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            userIdToDelete = this.dataset.userId;
            modal.style.display = 'flex';
        });
    });

    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        userIdToDelete = null;
    });

    confirmBtn.addEventListener('click', async () => {
        if (userIdToDelete) {
            try {
                const response = await fetch('/nmims_quiz_app/api/admin/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userIdToDelete })
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById(`user-row-${userIdToDelete}`).remove();
                } else {
                    throw new Error(result.error || 'Failed to delete user.');
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            } finally {
                modal.style.display = 'none';
                userIdToDelete = null;
            }
        }
    });
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
