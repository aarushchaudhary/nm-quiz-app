<?php
  $pageTitle = 'Question Item Analysis';
  $customCSS = 'manage.css';
  require_once '../../assets/templates/header.php';

  $quiz_id = isset($_GET['quiz_id']) ? filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT) : null;
?>

<div class="manage-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Item Analysis</h2>
        <a href="reports.php" class="button-red" style="width:auto; padding: 8px 16px; text-decoration:none;">&larr; Back to Reports</a>
    </div>
    <div id="analysis-content">
        <p>Loading analysis data...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const quizId = <?php echo json_encode($quiz_id); ?>;
    const contentDiv = document.getElementById('analysis-content');

    if (!quizId) {
        contentDiv.innerHTML = '<p style="color:red;">Error: No Quiz ID specified.</p>';
        return;
    }

    try {
        const response = await fetch(`/nmims_quiz_app/api/faculty/get_item_analysis.php?quiz_id=${quizId}`);
        if (!response.ok) throw new Error('Failed to load analysis data.');
        const analysisData = await response.json();

        contentDiv.innerHTML = ''; // Clear loading message

        if (analysisData.length === 0) {
            contentDiv.innerHTML = '<p>No auto-graded questions found to analyze for this quiz.</p>';
            return;
        }

        analysisData.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'analysis-card';
            
            const correctness = item.total_responses > 0 ? Math.round((item.correct_count / item.total_responses) * 100) : 0;
            
            let optionsHtml = '<ul class="analysis-options-list">';
            item.options.forEach(option => {
                const count = item.option_counts[option.id] || 0;
                const isCorrectClass = option.is_correct ? 'correct' : '';
                optionsHtml += `
                    <li class="analysis-option-item ${isCorrectClass}">
                        <span class="option-text">${escapeHTML(option.option_text)}</span>
                        <span class="option-count">${count} response(s)</span>
                    </li>
                `;
            });
            optionsHtml += '</ul>';

            card.innerHTML = `
                <h4>Question ${index + 1}: ${escapeHTML(item.question_text)}</h4>
                <div class="analysis-stats">
                    <span>Total Responses: ${item.total_responses}</span>
                    <span>Correct Answers: <strong>${item.correct_count} (${correctness}%)</strong></span>
                </div>
                ${optionsHtml}
            `;
            contentDiv.appendChild(card);
        });

    } catch (error) {
        contentDiv.innerHTML = `<p style="color:red;">Error: ${error.message}</p>`;
    }
});

function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}
</script>

<?php
  require_once '../../assets/templates/footer.php';
?>
