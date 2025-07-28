<?php
  $pageTitle = 'Event Log Report';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- **FIX:** Updated Authorization Check ---
  // Now allows any user who is NOT a student (role_id 4) to access this page.
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] == 4) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // Fetch all quizzes to populate the dropdown
  $quizzes_stmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY created_at DESC");
  $quizzes = $quizzes_stmt->fetchAll();
  
  $preselected_quiz_id = isset($_GET['quiz_id']) ? filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT) : null;
?>

<div class="manage-container">
    <div class="report-header">
        <h2>Exam Event Logs</h2>
        <form class="report-selector" id="log-selector-form">
            <div class="form-group">
                <label for="quiz_id_selector">Select a Quiz:</label>
                <select id="quiz_id_selector" name="quiz_id" class="input-field" style="padding: 8px;">
                    <option value="">-- Choose a Quiz --</option>
                    <?php foreach ($quizzes as $quiz): ?>
                        <option value="<?php echo $quiz['id']; ?>" <?php if ($quiz['id'] == $preselected_quiz_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($quiz['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <table class="data-table" id="log-table" style="display:none;">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Student Name</th>
                <th>Event Type</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody id="log-table-body"></tbody>
    </table>
    <p id="log-placeholder">Please select a quiz to view its event log.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quizSelector = document.getElementById('quiz_id_selector');
    const logTable = document.getElementById('log-table');
    const placeholder = document.getElementById('log-placeholder');
    const tableBody = document.getElementById('log-table-body');

    async function loadLogs(quizId) {
        if (!quizId) {
            logTable.style.display = 'none';
            placeholder.style.display = 'block';
            return;
        }

        try {
            const response = await fetch(`/nmims_quiz_app/api/shared/get_event_logs.php?quiz_id=${quizId}`);
            if (!response.ok) throw new Error('Failed to fetch logs.');
            const logs = await response.json();
            
            tableBody.innerHTML = '';
            if (logs.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No events have been logged for this quiz.</td></tr>';
            } else {
                logs.forEach(log => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${new Date(log.timestamp).toLocaleString()}</td>
                        <td>${log.student_name}</td>
                        <td>${log.event_type}</td>
                        <td>${log.description}</td>
                        <td>${log.ip_address}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            }
            placeholder.style.display = 'none';
            logTable.style.display = 'table';
        } catch (error) {
            console.error("Error loading event logs:", error);
            placeholder.textContent = 'Error loading log data.';
        }
    }

    quizSelector.addEventListener('change', function() {
        loadLogs(this.value);
    });

    if (quizSelector.value) {
        loadLogs(quizSelector.value);
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
