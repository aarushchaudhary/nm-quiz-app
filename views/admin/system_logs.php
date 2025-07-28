<?php
  $pageTitle = 'System Event Logs';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check for Admin ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // Fetch data for filter dropdowns
  $quizzes = $pdo->query("SELECT id, title FROM quizzes ORDER BY title ASC")->fetchAll();
  $users = $pdo->query("SELECT u.id, COALESCE(s.name, f.name, p.name, a.name, h.name) as full_name, u.username FROM users u LEFT JOIN students s ON u.id = s.user_id LEFT JOIN faculties f ON u.id = f.user_id LEFT JOIN placement_officers p ON u.id = p.user_id LEFT JOIN admins a ON u.id = a.user_id LEFT JOIN heads h ON u.id = h.user_id ORDER BY full_name ASC")->fetchAll();

  // --- **FIX:** Build the dynamic SQL query using the correct COALESCE for names ---
  $sql = "SELECT 
            el.timestamp, el.event_type, el.description, el.ip_address,
            q.title as quiz_title,
            COALESCE(s.name, f.name, p.name, a.name, h.name) as user_name
          FROM event_logs el
          LEFT JOIN users u ON el.user_id = u.id
          LEFT JOIN students s ON u.id = s.user_id
          LEFT JOIN faculties f ON u.id = f.user_id
          LEFT JOIN placement_officers p ON u.id = p.user_id
          LEFT JOIN admins a ON u.id = a.user_id
          LEFT JOIN heads h ON u.id = h.user_id
          LEFT JOIN student_attempts sa ON el.attempt_id = sa.id
          LEFT JOIN quizzes q ON sa.quiz_id = q.id";
  
  $where_clauses = [];
  $params = [];

  $quiz_filter = $_GET['quiz_filter'] ?? '';
  $user_filter = $_GET['user_filter'] ?? '';
  $search_query = $_GET['search_query'] ?? '';

  if (!empty($quiz_filter)) {
      $where_clauses[] = "q.id = ?";
      $params[] = $quiz_filter;
  }
  if (!empty($user_filter)) {
      $where_clauses[] = "el.user_id = ?";
      $params[] = $user_filter;
  }
  if (!empty($search_query)) {
      $search_term = '%' . $search_query . '%';
      $where_clauses[] = "(el.description LIKE ? OR el.event_type LIKE ?)";
      array_push($params, $search_term, $search_term);
  }

  if (!empty($where_clauses)) {
      $sql .= " WHERE " . implode(' AND ', $where_clauses);
  }

  $sql .= " ORDER BY el.timestamp DESC";
  
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $logs = $stmt->fetchAll();
?>

<div class="manage-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>System Event Logs</h2>
        <a href="dashboard.php" class="button-red" style="width:auto; background-color:#6c757d;">&larr; Back to Dashboard</a>
    </div>

    <div class="section-box">
        <form method="GET" action="system_logs.php" class="form-container" style="padding:0; box-shadow:none;">
            <div class="form-row">
                <div class="form-group">
                    <label for="quiz_filter">Filter by Quiz</label>
                    <select id="quiz_filter" name="quiz_filter" class="input-field">
                        <option value="">All Quizzes</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?php echo $quiz['id']; ?>" <?php if($quiz['id'] == $quiz_filter) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="user_filter">Filter by User</label>
                    <select id="user_filter" name="user_filter" class="input-field">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php if($user['id'] == $user_filter) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="search_query">Search Description / Type</label>
                    <input type="text" id="search_query" name="search_query" class="input-field" placeholder="e.g., Violation, Login..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
            </div>
            <div class="button-group" style="justify-content: flex-start;">
                <button type="submit" class="button-red" style="width:auto;">Filter Logs</button>
                <a href="system_logs.php" class="button-red" style="width:auto; background-color:#6c757d;">Clear Filters</a>
            </div>
        </form>
    </div>

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
                <tr><td colspan="6" style="text-align:center;">No system events found matching your criteria.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M j, Y, g:i:s A', strtotime($log['timestamp'])); ?></td>
                        <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
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
