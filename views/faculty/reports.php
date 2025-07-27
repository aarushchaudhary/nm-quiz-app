<?php
  $pageTitle = 'Quiz Reports';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';
  require_once '../../config/database.php';

  // --- Authorization Check ---
  // This check allows multiple roles (e.g., Admin, Faculty, TA).
  // The old file only allowed role_id 2. This is based on the new file's logic.
  if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
      header('Location: /nmims_quiz_app/login.php');
      exit();
  }
  $faculty_id = $_SESSION['user_id'];

  // Fetch all quizzes created by this faculty to populate the dropdown
  $quizzes_stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE faculty_id = ? ORDER BY created_at DESC");
  $quizzes_stmt->execute([$faculty_id]);
  $quizzes = $quizzes_stmt->fetchAll();
  
  // Check if a specific quiz was passed in the URL to pre-select it
  $preselected_quiz_id = isset($_GET['quiz_id']) ? filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT) : null;
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
                            <!-- Pre-select the option if its ID matches the one from the URL -->
                            <option value="<?php echo $quiz['id']; ?>" <?php if ($quiz['id'] == $preselected_quiz_id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <a href="#" id="analysis-btn" class="button-red" style="display:none; width:auto; padding: 10px 20px; background-color: #6f42c1;">Item Analysis</a>
            <a href="#" id="evaluate-btn" class="button-red" style="display:none; width:auto; padding: 10px 20px; background-color: #ffc107; color: #333;">Evaluate Answers</a>
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
document.addEventListener('DOMContentLoaded', function() {
    const quizSelector = document.getElementById('quiz_id_selector');
    const reportContent = document.getElementById('report-content');
    const placeholder = document.getElementById('report-placeholder');
    const exportBtn = document.getElementById('export-btn');
    const evaluateBtn = document.getElementById('evaluate-btn');
    const analysisBtn = document.getElementById('analysis-btn');

    // This function fetches and displays the report for a given quiz ID
    async function loadReport(quizId) {
        if (!quizId) {
            reportContent.style.display = 'none';
            exportBtn.style.display = 'none';
            evaluateBtn.style.display = 'none';
            analysisBtn.style.display = 'none';
            placeholder.textContent = 'Please select a quiz to view the report.';
            placeholder.style.display = 'block';
            return;
        }

        placeholder.textContent = 'Loading report...';
        placeholder.style.display = 'block';
        reportContent.style.display = 'none';

        try {
            const response = await fetch(`/nmims_quiz_app/api/faculty/get_quiz_results.php?quiz_id=${quizId}`);
            if (!response.ok) {
                let errorMsg = 'Failed to fetch results.';
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.error || errorMsg;
                } catch (e) {
                    // Response was not JSON, do nothing and use the default error message.
                }
                throw new Error(errorMsg);
            }
            const data = await response.json();
            
            // **FIXED:** This is the data population logic from the original file, now placed inside the new function.
            document.getElementById('summary-attempts').textContent = data.summary.total_attempts;
            document.getElementById('summary-avg-score').textContent = data.summary.average_score;
            document.getElementById('summary-disqualified').textContent = data.summary.disqualified_count;

            const tableBody = document.getElementById('results-table-body');
            tableBody.innerHTML = ''; // Clear previous results
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
            // --- End of restored logic ---

            placeholder.style.display = 'none';
            reportContent.style.display = 'block';
            
            // Update and show the action buttons
            exportBtn.href = `/nmims_quiz_app/api/faculty/export_results.php?quiz_id=${quizId}`;
            exportBtn.style.display = 'inline-block';
            evaluateBtn.href = `evaluate_descriptive.php?quiz_id=${quizId}`;
            evaluateBtn.style.display = 'inline-block';
            analysisBtn.href = `item_analysis.php?quiz_id=${quizId}`;
            analysisBtn.style.display = 'inline-block';

        } catch (error) {
            console.error("Error loading report:", error);
            placeholder.textContent = `Error: ${error.message}`;
            placeholder.style.display = 'block';
            reportContent.style.display = 'none';
        }
    }

    // Add event listener for when the user manually changes the quiz selection
    quizSelector.addEventListener('change', function() {
        loadReport(this.value);
    });

    // Automatically load the report if a quiz is pre-selected via URL parameter
    if (quizSelector.value) {
        loadReport(quizSelector.value);
    }
});
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
