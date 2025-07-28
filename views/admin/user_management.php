<?php
  $pageTitle = 'User Management';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check for Admin ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // --- Fetch data for the role filter dropdown ---
  $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();

  // --- Build the dynamic SQL query based on filters ---
  $sql = "SELECT
            u.id, u.username, r.name as role_name,
            COALESCE(s.name, f.name, p.name, a.name, h.name) as full_name,
            COALESCE(s.sap_id, f.sap_id, p.sap_id) as sap_id
          FROM users u
          JOIN roles r ON u.role_id = r.id
          LEFT JOIN students s ON u.id = s.user_id AND u.role_id = 4
          LEFT JOIN faculties f ON u.id = f.user_id AND u.role_id = 2
          LEFT JOIN placement_officers p ON u.id = p.user_id AND u.role_id = 3
          LEFT JOIN admins a ON u.id = a.user_id AND u.role_id = 1
          LEFT JOIN heads h ON u.id = h.user_id";

  $where_clauses = [];
  $params = [];

  $search_query_val = $_GET['search_query'] ?? '';
  $role_filter_val = $_GET['role_filter'] ?? '';

  if (!empty($search_query_val)) {
      $search_term = '%' . $search_query_val . '%';
      $where_clauses[] = "(COALESCE(s.name, f.name, p.name, a.name, h.name) LIKE ? OR u.username LIKE ? OR COALESCE(s.sap_id, f.sap_id, p.sap_id) LIKE ?)";
      array_push($params, $search_term, $search_term, $search_term);
  }

  if (!empty($role_filter_val)) {
      $where_clauses[] = "u.role_id = ?";
      $params[] = $role_filter_val;
  }

  if (!empty($where_clauses)) {
      $sql .= " WHERE " . implode(' AND ', $where_clauses);
  }

  $sql .= " ORDER BY u.id DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $users = $stmt->fetchAll();
?>

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
<div class="confirm-modal-overlay" id="reset-password-modal">
    <div class="confirm-modal">
        <h3>Reset Password</h3>
        <p>Enter a new password for the user: <strong id="reset-username-display"></strong></p>
        <form class="reset-password-form" id="reset-password-form"><div class="form-group"><label for="new_password">New Password</label><input type="password" id="new_password" name="new_password" class="input-field" required></div></form>
        <div class="button-group">
            <button class="btn-cancel" id="cancel-reset-btn">Cancel</button>
            <button class="button-red" id="confirm-reset-btn" style="background-color:#28a745;">Update Password</button>
        </div>
    </div>
</div>

<div class="manage-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>All Users (<?php echo count($users); ?>)</h2>
        <a href="add_user.php" class="button-red" style="width: auto; padding: 10px 20px;">+ Add New User</a>
    </div>

    <div class="section-box" style="margin-top: 15px;">
        <form method="GET" action="user_management.php" class="form-container" style="padding:0; box-shadow:none;">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="search_query">Search by Name / Username / SAP ID</label>
                    <input type="text" id="search_query" name="search_query" class="input-field" placeholder="Enter search term..." value="<?php echo htmlspecialchars($search_query_val); ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="role_filter">Filter by Role</label>
                    <select id="role_filter" name="role_filter" class="input-field">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" <?php if($role['id'] == $role_filter_val) echo 'selected'; ?>>
                                <?php echo htmlspecialchars(ucfirst($role['name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="button-group" style="justify-content: flex-start;">
                <button type="submit" class="button-red" style="width:auto;">Filter</button>
                <a href="user_management.php" class="button-red" style="width:auto; background-color:#6c757d;">Clear</a>
            </div>
        </form>
    </div>

    <?php
    if (isset($_GET['success'])) { echo '<div class="message-box success-message">' . htmlspecialchars($_GET['success']) . '</div>'; }
    if (isset($_GET['error'])) { echo '<div class="message-box error-message">' . htmlspecialchars($_GET['error']) . '</div>'; }
    ?>

    <table class="data-table">
        <thead>
            <tr><th>Full Name</th><th>Username</th><th>SAP ID</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody id="user-table-body">
            <?php foreach ($users as $user): ?>
                <tr id="user-row-<?php echo $user['id']; ?>">
                    <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['sap_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($user['role_name'])); ?></td>
                    <td class="action-buttons" style="flex-direction:row; gap:5px;">
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-edit">Edit</a>
                        <button class="btn-reset-password" data-user-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" style="background-color:#007bff;">Reset Pass</button>
                        <button class="btn-delete" data-user-id="<?php echo $user['id']; ?>" style="background-color:#dc3545;">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Delete Modal Logic ---
    const deleteModal = document.getElementById('delete-confirm-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    let userIdToDelete = null;

    // --- Reset Password Modal Logic ---
    const resetModal = document.getElementById('reset-password-modal');
    const cancelResetBtn = document.getElementById('cancel-reset-btn');
    const confirmResetBtn = document.getElementById('confirm-reset-btn');
    const resetUsernameDisplay = document.getElementById('reset-username-display');
    const newPasswordField = document.getElementById('new_password');
    let userIdToReset = null;

    // Use event delegation for the whole table body
    document.getElementById('user-table-body').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete')) {
            userIdToDelete = e.target.dataset.userId;
            deleteModal.style.display = 'flex';
        }
        if (e.target.classList.contains('btn-reset-password')) {
            userIdToReset = e.target.dataset.userId;
            resetUsernameDisplay.textContent = e.target.dataset.username;
            resetModal.style.display = 'flex';
            newPasswordField.focus();
        }
    });

    // --- **FIX:** Event Listeners for Modals with full logic ---
    cancelDeleteBtn.addEventListener('click', () => {
        deleteModal.style.display = 'none';
        userIdToDelete = null;
    });

    confirmDeleteBtn.addEventListener('click', async () => {
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
                deleteModal.style.display = 'none';
                userIdToDelete = null;
            }
        }
    });

    cancelResetBtn.addEventListener('click', () => {
        resetModal.style.display = 'none';
        newPasswordField.value = '';
        userIdToReset = null;
    });

    confirmResetBtn.addEventListener('click', async () => {
        if (userIdToReset && newPasswordField.value) {
            try {
                const response = await fetch('/nmims_quiz_app/api/admin/reset_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userIdToReset, new_password: newPasswordField.value })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Password updated successfully!');
                } else {
                    throw new Error(result.error || 'Failed to reset password.');
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            } finally {
                cancelResetBtn.click();
            }
        }
    });
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
