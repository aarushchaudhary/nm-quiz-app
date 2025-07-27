<?php
  $pageTitle = 'System Event Logs';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check for Admin (role_id = 1) ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // --- Fetch all event logs ---
  // This query joins multiple tables to get all the necessary context for each log entry.
  $sql = "SELECT 
            el.timestamp,
            el.event_type,
            el.description,
            el.ip_address,
            q.title as quiz_title,
            COALESCE(s.name, f.name, p.name, a.name) as user_name
          FROM event_logs el
          LEFT JOIN users u ON el.user_id = u.id
          LEFT JOIN students s ON u.id = s.user_id
          LEFT JOIN faculties f ON u.id = f.user_id
          LEFT JOIN placement_officers p ON u.id = p.user_id
          LEFT JOIN admins a ON u.id = a.user_id
          LEFT JOIN student_attempts sa ON el.attempt_id = sa.id
          LEFT JOIN quizzes q ON sa.quiz_id = q.id
          ORDER BY el.timestamp DESC";
  
  $stmt = $pdo->query($sql);
  $logs = $stmt->fetchAll();
?>

<div class="manage-container">
    <a href="dashboard.php" style="text-decoration: none; color: #007bff; margin-bottom: 20px; display: inline-block;">&larr; Back to Dashboard</a>
    <h2>System Event Logs</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Quiz</th>
                <th>Event Type</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="text-align:center;">No system events have been logged yet.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M j, Y, g:i:s A', strtotime($log['timestamp'])); ?></td>
                        <td><?php echo htmlspecialchars($log['user_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['quiz_title'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['event_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
  require_once '../../assets/templates/footer.php';
?>
