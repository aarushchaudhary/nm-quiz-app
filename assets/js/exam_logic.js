/*
 * exam_logic.js
 * Handles all client-side logic for the student exam page.
 */
document.addEventListener('DOMContentLoaded', async function() {
    // --- State and UI Variables ---
    const ui = {
        examContainer: document.getElementById('exam-container'),
        loadingOverlay: document.getElementById('loading-overlay'),
        warningOverlay: document.getElementById('warning-overlay'),
        warningCountSpan: document.getElementById('warning-count'),
        questionCounter: document.getElementById('question-counter'),
        timer: document.getElementById('timer'),
        questionText: document.getElementById('question-text'),
        optionsGrid: document.getElementById('options-grid'),
        nextBtn: document.getElementById('next-btn'),
        clickPrompt: document.getElementById('click-prompt')
    };

    const quizId = ui.examContainer.dataset.quizId;
    const examState = { questions: [], currentQuestionIndex: 0, attemptId: null, questionStartTime: null };
    const proctoringState = { warningCount: 0, examFinished: false };

    // --- Main Exam Functions ---
    async function startExam() {
        try {
            const response = await fetch(`/nmims_quiz_app/api/student/fetch_exam_questions.php?id=${quizId}`);
            const data = await response.json();
            if (!response.ok || data.error) {
                throw new Error(data.error || 'Failed to load exam data.');
            }
            examState.questions = data.questions;
            examState.attemptId = data.attempt_id;

            document.addEventListener('visibilitychange', handleVisibilityChange);
            document.addEventListener('fullscreenchange', handleFullscreenChange);
            document.addEventListener('contextmenu', event => event.preventDefault());
            document.addEventListener('keydown', handleKeyDown);
            
            if (examState.questions.length === 0) {
                throw new Error('This quiz has no questions. Please contact your faculty.');
            }
            
            startTimer(data.remaining_seconds);
            renderQuestion();
            setInterval(checkQuizStatus, 10000);
            ui.loadingOverlay.style.display = 'none';
            ui.examContainer.style.display = 'flex';

        } catch (error) {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            document.removeEventListener('fullscreenchange', handleFullscreenChange);
            document.removeEventListener('contextmenu', event => event.preventDefault());
            document.removeEventListener('keydown', handleKeyDown);
            alert(`Error starting exam: ${error.message}`);
            window.location.href = 'dashboard.php';
        }
    }

    function renderQuestion() {
        const q = examState.questions[examState.currentQuestionIndex];
        ui.questionCounter.textContent = `Question ${examState.currentQuestionIndex + 1} of ${examState.questions.length}`;
        ui.questionText.textContent = q.question_text;
        ui.optionsGrid.innerHTML = '';
        if (q.question_type_id == 3) {
            ui.optionsGrid.innerHTML = `<textarea id="descriptive-answer" class="descriptive-answer-area" placeholder="Type your answer here..." spellcheck="false"></textarea>`;
        } else {
            const inputType = q.question_type_id == 1 ? 'radio' : 'checkbox';
            q.options.forEach(opt => {
                const label = document.createElement('label');
                label.className = 'option-label';
                label.innerHTML = `<input type="${inputType}" name="option" value="${opt.id}"> <span>${opt.option_text}</span>`;
                ui.optionsGrid.appendChild(label);
            });
        }
        examState.questionStartTime = Date.now();
        ui.nextBtn.textContent = (examState.currentQuestionIndex === examState.questions.length - 1) ? 'Finish Exam' : 'Next Question';
        ui.nextBtn.disabled = true;
    }

    async function saveCurrentAnswer() {
        if (proctoringState.examFinished) return;
        const q = examState.questions[examState.currentQuestionIndex];
        const timeSpent = Math.round((Date.now() - examState.questionStartTime) / 1000);
        let payload = {
            attempt_id: examState.attemptId,
            question_id: q.id,
            time_spent: timeSpent,
            selected_option_ids: [],
            answer_text: ''
        };
        if (q.question_type_id == 3) {
            payload.answer_text = document.getElementById('descriptive-answer').value;
        } else {
            payload.selected_option_ids = Array.from(ui.optionsGrid.querySelectorAll('input:checked')).map(i => i.value);
        }
        try {
            await fetch('/nmims_quiz_app/api/student/save_answer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        } catch (error) { console.error('Failed to save answer:', error); }
    }

    async function finishExam(isDisqualified = false) {
        if (proctoringState.examFinished) return;
        proctoringState.examFinished = true;
        ui.nextBtn.disabled = true;
        ui.nextBtn.textContent = 'Submitting...';
        await saveCurrentAnswer();
        try {
            await fetch('/nmims_quiz_app/api/student/finish_exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attempt_id: examState.attemptId, is_disqualified: isDisqualified })
            });
            if (isDisqualified) {
                window.location.href = `disqualified.php?attempt_id=${examState.attemptId}`;
            } else {
                alert('Exam Finished! Your answers have been submitted.');
                window.location.href = `results.php?attempt_id=${examState.attemptId}`;
            }
        } catch (error) {
            alert('Could not submit exam. Please check your connection.');
            ui.nextBtn.disabled = false;
        }
    }

    function startTimer(totalSeconds) {
        let timeLeft = parseInt(totalSeconds, 10) || 0;
        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (!proctoringState.examFinished) {
                    ui.timer.textContent = 'Time Up!';
                    alert('Time is up! Submitting your exam automatically.');
                    finishExam();
                }
                return;
            }
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            ui.timer.textContent = `Time Left: ${minutes}:${seconds}`;
        }, 1000);
    }
    
    async function checkQuizStatus() {
        if (proctoringState.examFinished) return;
        try {
            const response = await fetch(`/nmims_quiz_app/api/shared/get_quiz_status.php?id=${quizId}`);
            const data = await response.json();
            if (data.status && data.status !== 'In Progress') {
                alert('The faculty has ended the exam. Your answers will now be submitted.');
                finishExam();
            }
        } catch (error) {
            console.error('Could not check quiz status:', error);
        }
    }
    
    function enterFullscreen() { document.documentElement.requestFullscreen().catch(err => console.error(err)); }
    
    async function logViolation(description) {
        if (!examState.attemptId) {
            console.error("Attempt ID not available. Cannot log violation.");
            return;
        }
        await fetch('/nmims_quiz_app/api/student/log_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                attempt_id: examState.attemptId,
                event_type: 'Violation',
                description: description
            })
        });
    }

    function triggerViolation(description) {
        if (proctoringState.examFinished) return;
        proctoringState.warningCount++;
        logViolation(description);
        if (proctoringState.warningCount >= 2) {
            finishExam(true);
        } else {
            ui.warningCountSpan.textContent = `${proctoringState.warningCount} of 2`;
            ui.warningOverlay.style.display = 'flex';
            setTimeout(() => { ui.warningOverlay.style.display = 'none'; }, 4000);
        }
    }

    function handleVisibilityChange() {
        if (document.hidden) {
            triggerViolation(`User left the exam tab. Warning #${proctoringState.warningCount + 1}.`);
        }
    }

    function handleFullscreenChange() {
        if (!document.fullscreenElement) {
            triggerViolation(`User exited fullscreen. Warning #${proctoringState.warningCount + 1}.`);
            setTimeout(enterFullscreen, 1000);
        }
    }

    function handleKeyDown(event) {
        if (proctoringState.examFinished) return;
        if (event.metaKey || event.altKey) {
            event.preventDefault();
            triggerViolation(`Attempted to use a system command (Windows/Alt key). Warning #${proctoringState.warningCount + 1}.`);
        }
        const key = event.key.toUpperCase();
        const ctrl = event.ctrlKey;
        const shift = event.shiftKey;
        if (key === 'F12' || (ctrl && shift && key === 'I') || (ctrl && shift && key === 'J') || (ctrl && key === 'U')) {
            event.preventDefault();
            triggerViolation(`Attempted to use developer tools shortcut (${event.key}). Warning #${proctoringState.warningCount + 1}.`);
        }
        // **NEW:** Add a check for Ctrl+Shift+R
        if (ctrl && shift && key === 'R') {
            event.preventDefault(); // This will attempt to block the hard refresh
            triggerViolation(`Attempted to hard refresh the page (Ctrl+Shift+R). Warning #${proctoringState.warningCount + 1}.`);
        }
    }

    ui.nextBtn.addEventListener('click', async () => {
        if (examState.currentQuestionIndex < examState.questions.length - 1) {
            await saveCurrentAnswer();
            examState.currentQuestionIndex++;
            renderQuestion();
        } else {
            finishExam();
        }
    });

    ui.optionsGrid.addEventListener('input', () => {
        if (examState.questions[examState.currentQuestionIndex].question_type_id == 3) {
            ui.nextBtn.disabled = document.getElementById('descriptive-answer').value.trim() === '';
        } else {
            ui.nextBtn.disabled = ui.optionsGrid.querySelectorAll('input:checked').length === 0;
        }
    });
    
    async function initializeExam() {
        document.body.classList.add('exam-mode');
        if (ui.clickPrompt) {
            ui.clickPrompt.textContent = 'Click anywhere to begin the exam.';
        }
        
        document.body.addEventListener('click', async () => {
            enterFullscreen();
            await startExam();
        }, { once: true });
    }

    if(quizId) {
        initializeExam();
    } else {
        window.location.href = 'dashboard.php';
    }
});
