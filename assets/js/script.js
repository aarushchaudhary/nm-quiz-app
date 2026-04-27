/*
 * script.js
 * Merged file containing:
 * - Login page logic (from login.js)
 * - Exam page logic (from exam_logic.js)
 * Both modules are independent and wrapped in DOMContentLoaded listeners.
 */

/* ========== LOGIN MODULE ========== */
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const messageBox = document.getElementById('message-box');

    // Only run login logic if login form exists
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/nmims_quiz_app/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    window.location.href = '/nmims_quiz_app/index.php';
                } else if (result.status === 'conflict') {
                    // Show a confirmation dialog
                    if (confirm(result.message)) {
                        // If user clicks OK, send a "force login" request
                        forceLogin(data);
                    }
                } else {
                    throw new Error(result.message || 'An unknown error occurred.');
                }
            } catch (error) {
                messageBox.textContent = `Error: ${error.message}`;
                messageBox.style.display = 'block';
            }
        });

        async function forceLogin(data) {
            data.force = true; // Add the force flag
            try {
                const response = await fetch('/nmims_quiz_app/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.href = '/nmims_quiz_app/index.php';
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                messageBox.textContent = `Error: ${error.message}`;
                messageBox.style.display = 'block';
            }
        }
    }
});

/* ========== EXAM MODULE ========== */
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

    // Only run exam logic if exam container exists
    if (!ui.examContainer) return;

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

            // Anti-cheating mechanisms removed. Original listeners:
            // - visibilitychange, fullscreenchange, contextmenu, keydown
            
            if (examState.questions.length === 0) {
                throw new Error('This quiz has no questions. Please contact your faculty.');
            }
            
            startTimer(data.remaining_seconds);
            renderQuestion();
            setInterval(checkQuizStatus, 10000);
            ui.loadingOverlay.style.display = 'none';
            ui.examContainer.style.display = 'flex';

        } catch (error) {
            // Removed event listener cleanup (previously removed handlers no longer need cleanup)
            alert(`Error starting exam: ${error.message}`);
            window.location.href = 'dashboard.php';
        }
    }

    /**
     * Renders the current question or the final submission screen.
     */
    function renderQuestion() {
        const q = examState.questions[examState.currentQuestionIndex];
        ui.questionCounter.textContent = `Question ${examState.currentQuestionIndex + 1} of ${examState.questions.length}`;
        ui.questionText.textContent = q.question_text;
        ui.optionsGrid.innerHTML = '';

        // Check if it's the special final submission question
        if (q.id === 'final_submit') {
            ui.optionsGrid.innerHTML = `
                <label class="option-label"><input type="radio" name="option" value="yes"> <span>Yes, submit my exam.</span></label>
                <label class="option-label"><input type="radio" name="option" value="no" checked> <span>No, I want to review my answers.</span></label>
            `;
            ui.nextBtn.textContent = 'Finish Exam';
            ui.nextBtn.disabled = false;
        } else {
            // It's a regular question, render it based on its type
            if (q.question_type_id == 3) {
                ui.optionsGrid.innerHTML = `<textarea id="descriptive-answer" class="descriptive-answer-area" placeholder="Type your answer here..." spellcheck="false" data-gramm="false"></textarea>`;
            } else {
                const inputType = q.question_type_id == 1 ? 'radio' : 'checkbox';
                q.options.forEach(opt => {
                    const label = document.createElement('label');
                    label.className = 'option-label';
                    label.innerHTML = `<input type="${inputType}" name="option" value="${opt.id}"> <span>${opt.option_text}</span>`;
                    ui.optionsGrid.appendChild(label);
                });
            }
            ui.nextBtn.textContent = (examState.currentQuestionIndex === examState.questions.length - 2) ? 'Next (Final Question)' : 'Next Question';
            ui.nextBtn.disabled = true;
        }
        
        examState.questionStartTime = Date.now();
    }

    async function saveCurrentAnswer() {
        if (proctoringState.examFinished) return;
        const q = examState.questions[examState.currentQuestionIndex];

        if (q.id === 'final_submit') return;

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
            const response = await fetch('/nmims_quiz_app/api/student/save_answer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to save answer.');
            }
        } catch (error) {
            console.error(error);
            alert(`Error saving answer: ${error.message}`);
        }
    }

    /**
     * Finishes the exam, sending the last answer if it's a normal submission.
     */
    async function finishExam(isDisqualified = false) {
        if (proctoringState.examFinished) return;
        proctoringState.examFinished = true;
        
        ui.nextBtn.disabled = true;
        ui.nextBtn.textContent = 'Submitting...';

        let bodyPayload = { 
            attempt_id: examState.attemptId, 
            is_disqualified: isDisqualified 
        };

        // Only send the last answer if it's a normal (not disqualified) submission.
        if (!isDisqualified) {
            const lastQuestion = examState.questions[examState.currentQuestionIndex];
            const timeSpent = Math.round((Date.now() - examState.questionStartTime) / 1000);
            bodyPayload.last_answer = {
                question_id: lastQuestion.id,
                time_spent: timeSpent,
                selected_option_ids: (lastQuestion.question_type_id != 3) ? Array.from(ui.optionsGrid.querySelectorAll('input:checked')).map(i => i.value) : [],
                answer_text: (lastQuestion.question_type_id == 3) ? document.getElementById('descriptive-answer').value : ''
            };
        }

        try {
            await fetch('/nmims_quiz_app/api/student/finish_exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bodyPayload)
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
    
    // --- Proctoring & Status Check Functions ---
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
    
    // REMOVED: enterFullscreen() function - fullscreen enforcement removed
    // REMOVED: logViolation() function - violation logging removed
    // REMOVED: triggerViolation() function - violation triggering removed


    // --- Event Listeners ---
    ui.nextBtn.addEventListener('click', async () => {
        const currentQuestion = examState.questions[examState.currentQuestionIndex];
        
        if (currentQuestion.id === 'final_submit') {
            const decision = ui.optionsGrid.querySelector('input:checked').value;
            if (decision === 'yes') {
                finishExam();
            } else {
                examState.currentQuestionIndex--;
                renderQuestion();
            }
        } else {
            await saveCurrentAnswer();
            if (examState.currentQuestionIndex < examState.questions.length - 1) {
                examState.currentQuestionIndex++;
                renderQuestion();
            } else {
                finishExam();
            }
        }
    });

    ui.optionsGrid.addEventListener('input', () => {
        const currentQuestion = examState.questions[examState.currentQuestionIndex];
        if (currentQuestion.id === 'final_submit') {
             ui.nextBtn.disabled = false;
             return;
        }

        if (currentQuestion.question_type_id == 3) {
            ui.nextBtn.disabled = document.getElementById('descriptive-answer').value.trim() === '';
        } else {
            ui.nextBtn.disabled = ui.optionsGrid.querySelectorAll('input:checked').length === 0;
        }
    });
    
    // --- Initializer ---
    async function initializeExam() {
        document.body.classList.add('exam-mode');
        if (ui.clickPrompt) {
            ui.clickPrompt.textContent = 'Click anywhere to begin the exam.';
        }
        
        document.body.addEventListener('click', async () => {
            // Removed: enterFullscreen() call - fullscreen enforcement removed
            await startExam();
        }, { once: true });
    }

    if(quizId) {
        initializeExam();
    } else {
        window.location.href = 'dashboard.php';
    }
});
