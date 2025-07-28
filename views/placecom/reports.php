<?php
  $pageTitle = 'All Quiz Reports';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check (Allows any non-student role) ---
  if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] == 4) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }

  // Fetch ALL quizzes from the database
  $quizzes_stmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY created_at DESC");
  $quizzes = $quizzes_stmt->fetchAll();
?>

<div class="manage-container">
    <div class="report-header">
        <h2>View All Quiz Results</h2>
        <div style="display: flex; align-items: center; gap: 20px;">
            <form class="report-selector" id="report-selector-form">
                <div class="form-group">
                    <label for="quiz_id_selector">Select a Quiz:</label>
                    <select id="quiz_id_selector" name="quiz_id" class="input-field" style="padding: 8px;">
                        <option value="">-- Choose a Quiz --</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?php echo $quiz['id']; ?>"><?php echo htmlspecialchars($quiz['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <a href="#" id="export-btn" class="button-red" style="display:none; width:auto; padding: 10px 20px; background-color: #17a2b8;">Export to Excel</a>
        </div>
    </div>

    <div id="report-content" style="display:none;">
        <div class="report-summary-grid">
            <div class="summary-card"><p class="card-title">Total Attempts</p><p class="card-value" id="summary-attempts">0</p></div>
            <div class="summary-card"><p class="card-title">Average Score</p><p class="card-value" id="summary-avg-score">0.00</p></div>
            <div class="summary-card"><p class="card-title">Disqualified</p><p class="card-value" id="summary-disqualified">0</p></div>
        </div>
        <table class="data-table results-table">
            <thead><tr><th>Student Name</th><th>SAP ID</th><th>Score</th><th>Time Taken</th><th>Status</th></tr></thead>
            <tbody id="results-table-body"></tbody>
        </table>
    </div>
    <p id="report-placeholder">Please select a quiz to view the report.</p>
</div>

<script>
document.getElementById('quiz_id_selector').addEventListener('change', async function() {
    const quizId = this.value;
    const reportContent = document.getElementById('report-content');
    const placeholder = document.getElementById('report-placeholder');
    const exportBtn = document.getElementById('export-btn');

    if (!quizId) {
        reportContent.style.display = 'none';
        exportBtn.style.display = 'none';
        placeholder.style.display = 'block';
        return;
    }

    try {
        const response = await fetch(`/nmims_quiz_app/api/placecom/get_all_quiz_results.php?quiz_id=${quizId}`);
        if (!response.ok) throw new Error('Failed to fetch results.');
        const data = await response.json();
        
        document.getElementById('summary-attempts').textContent = data.summary.total_attempts;
        document.getElementById('summary-avg-score').textContent = data.summary.average_score;
        document.getElementById('summary-disqualified').textContent = data.summary.disqualified_count;

        const tableBody = document.getElementById('results-table-body');
        tableBody.innerHTML = '';
        if (data.details.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No attempts found for this quiz.</td></tr>';
        } else {
            data.details.forEach(row => {
                const tr = document.createElement('tr');
                if (row.is_disqualified) tr.classList.add('disqualified');
                let timeTaken = 'N/A';
                if (row.started_at && row.submitted_at) {
                    const diffSeconds = Math.round((new Date(row.submitted_at) - new Date(row.started_at)) / 1000);
                    timeTaken = `${Math.floor(diffSeconds / 60)}m ${diffSeconds % 60}s`;
                }
                tr.innerHTML = `<td>${row.student_name}</td><td>${row.sap_id}</td><td>${parseFloat(row.total_score).toFixed(2)}</td><td>${timeTaken}</td><td>${row.is_disqualified ? 'Disqualified' : 'Completed'}</td>`;
                tableBody.appendChild(tr);
            });
        }

        placeholder.style.display = 'none';
        reportContent.style.display = 'block';
        
        // **FIX:** The export button now points to the new shared script.
        exportBtn.href = `/nmims_quiz_app/api/shared/export_all_results.php?quiz_id=${quizId}`;
        exportBtn.style.display = 'inline-block';

    } catch (error) {
        console.error("Error loading report:", error);
        placeholder.textContent = 'Error loading report data.';
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
