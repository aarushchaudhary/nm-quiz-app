<?php
  $pageTitle = 'Quiz Reports';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  $faculty_id = $_SESSION['user_id'];

  // Fetch all quizzes created by this faculty to populate the dropdown
  $quizzes_stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE faculty_id = ? ORDER BY created_at DESC");
  $quizzes_stmt->execute([$faculty_id]);
  $quizzes = $quizzes_stmt->fetchAll();
?>

<div class="manage-container">
    <div class="report-header">
        <h2>View Quiz Results</h2>
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
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
            <a href="#" id="analysis-btn" class="button-red" style="display:none; width:auto; padding: 10px 20px; background-color: #6f42c1;">Item Analysis</a>
            <a href="#" id="evaluate-btn" class="button-red" style="display:none; width:auto; padding: 10px 20px; background-color: #ffc107; color: #333;">Evaluate Answers</a>
            <a href="#" id="export-btn" class="button-red" style="display:none; width:auto; padding: 10px 20px; background-color: #17a2b8;">Export to Excel</a>
        </div>
    </div>

    <!-- This section will be populated with data by JavaScript -->
    <div id="report-content" style="display:none;">
        <div class="report-summary-grid">
            <div class="summary-card">
                <p class="card-title">Total Attempts</p>
                <p class="card-value" id="summary-attempts">0</p>
            </div>
            <div class="summary-card">
                <p class="card-title">Average Score</p>
                <p class="card-value" id="summary-avg-score">0.00</p>
            </div>
            <div class="summary-card">
                <p class="card-title">Disqualified</p>
                <p class="card-value" id="summary-disqualified">0</p>
            </div>
        </div>

        <table class="data-table results-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>SAP ID</th>
                    <th>Score</th>
                    <th>Time Taken</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="results-table-body">
                <!-- Detailed results will be inserted here -->
            </tbody>
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
    const evaluateBtn = document.getElementById('evaluate-btn');
    const analysisBtn = document.getElementById('analysis-btn');

    if (!quizId) {
        reportContent.style.display = 'none';
        exportBtn.style.display = 'none';
        evaluateBtn.style.display = 'none';
        analysisBtn.style.display = 'none';
        placeholder.style.display = 'block';
        return;
    }

    try {
        const response = await fetch(`/nmims_quiz_app/api/faculty/get_quiz_results.php?quiz_id=${quizId}`);
        if (!response.ok) throw new Error('Failed to fetch results.');

        const data = await response.json();
        
        // Populate summary cards
        document.getElementById('summary-attempts').textContent = data.summary.total_attempts;
        document.getElementById('summary-avg-score').textContent = data.summary.average_score;
        document.getElementById('summary-disqualified').textContent = data.summary.disqualified_count;

        // Populate details table
        const tableBody = document.getElementById('results-table-body');
        tableBody.innerHTML = '';
        if (data.details.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No attempts found for this quiz.</td></tr>';
        } else {
            data.details.forEach(row => {
                const tr = document.createElement('tr');
                if (row.is_disqualified) {
                    tr.classList.add('disqualified');
                }
                let timeTaken = 'N/A';
                if (row.started_at && row.submitted_at) {
                    const start = new Date(row.started_at);
                    const end = new Date(row.submitted_at);
                    const diffSeconds = Math.round((end - start) / 1000);
                    const minutes = Math.floor(diffSeconds / 60);
                    const seconds = diffSeconds % 60;
                    timeTaken = `${minutes}m ${seconds}s`;
                }
                tr.innerHTML = `
                    <td>${row.student_name}</td>
                    <td>${row.sap_id}</td>
                    <td>${parseFloat(row.total_score).toFixed(2)}</td>
                    <td>${timeTaken}</td>
                    <td>${row.is_disqualified ? 'Disqualified' : 'Completed'}</td>
                `;
                tableBody.appendChild(tr);
            });
        }

        // Show the report and the action buttons
        placeholder.style.display = 'none';
        reportContent.style.display = 'block';
        
        exportBtn.href = `/nmims_quiz_app/api/faculty/export_results.php?quiz_id=${quizId}`;
        exportBtn.style.display = 'inline-block';
        
        evaluateBtn.href = `evaluate_descriptive.php?quiz_id=${quizId}`;
        evaluateBtn.style.display = 'inline-block';
        
        analysisBtn.href = `item_analysis.php?quiz_id=${quizId}`;
        analysisBtn.style.display = 'inline-block';

    } catch (error) {
        console.error("Error loading report:", error);
        placeholder.textContent = 'Error loading report data.';
        placeholder.style.display = 'block';
        reportContent.style.display = 'none';
        exportBtn.style.display = 'none';
        evaluateBtn.style.display = 'none';
        analysisBtn.style.display = 'none';
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
