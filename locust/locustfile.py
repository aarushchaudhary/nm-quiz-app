"""
locustfile.py — NMIMS Quiz App — Full Coverage Load Tests
==========================================================

Covers every readable endpoint and view page across all 5 roles.
Destructive write-only operations (create/delete/upload) are intentionally
excluded — use the unit test suite (tests/) for those.

User classes and their weights (ratio of virtual users):
  StudentUser    weight=6  — Full sequential exam journey
  FacultyUser    weight=3  — Quiz management + live monitoring
  AdminUser      weight=2  — User & academic management browsing
  PlacecomUser   weight=1  — Aggregated results browsing
  HeadUser       weight=1  — Department head dashboard

Configuration via environment variables (see .env):
  QUIZ_BASE_URL, STUDENT_EMAIL/PASSWORD, FACULTY_EMAIL/PASSWORD,
  ADMIN_EMAIL/PASSWORD, PLACECOM_EMAIL/PASSWORD, QUIZ_ID

Usage:
  source .venv/bin/activate
  source .env
  locust -f locustfile.py                        # Web UI → http://localhost:8089
  locust -f locustfile.py --headless -u 50 -r 5 -t 2m --csv=results/run
"""

import os
import random
import time
from pathlib import Path

from dotenv import load_dotenv
from locust import HttpUser, SequentialTaskSet, TaskSet, between, events, task

# Auto-load .env from the locust/ directory — no `source .env` needed
load_dotenv(dotenv_path=Path(__file__).parent / ".env")

# ── Configuration ──────────────────────────────────────────────────────────────
BASE_URL: str = os.getenv("QUIZ_BASE_URL", "http://localhost:8080")

STUDENT_EMAIL: str    = os.getenv("STUDENT_EMAIL", "student@nmims.edu")
STUDENT_PASSWORD: str = os.getenv("STUDENT_PASSWORD", "password123")

FACULTY_EMAIL: str    = os.getenv("FACULTY_EMAIL", "faculty@nmims.edu")
FACULTY_PASSWORD: str = os.getenv("FACULTY_PASSWORD", "password123")

ADMIN_EMAIL: str    = os.getenv("ADMIN_EMAIL", "admin@nmims.edu")
ADMIN_PASSWORD: str = os.getenv("ADMIN_PASSWORD", "password123")

PLACECOM_EMAIL: str    = os.getenv("PLACECOM_EMAIL", "placecom@nmims.edu")
PLACECOM_PASSWORD: str = os.getenv("PLACECOM_PASSWORD", "password123")

# Head role shares admin-level credentials unless overridden
HEAD_EMAIL: str    = os.getenv("HEAD_EMAIL", os.getenv("ADMIN_EMAIL", "admin@nmims.edu"))
HEAD_PASSWORD: str = os.getenv("HEAD_PASSWORD", os.getenv("ADMIN_PASSWORD", "password123"))

QUIZ_ID: int = int(os.getenv("QUIZ_ID", "1"))

# Slow-request warning threshold (ms)
SLOW_REQUEST_MS = 2000


# ══════════════════════════════════════════════════════════════════════════════
#  Shared helpers
# ══════════════════════════════════════════════════════════════════════════════

def _login(client, email: str, password: str, role_label: str) -> bool:
    """POST /api/auth.php and return True on success."""
    with client.post(
        "/api/auth.php",
        json={"email": email, "password": password, "force": True},
        name=f"[{role_label}] POST /api/auth.php",
        catch_response=True,
    ) as resp:
        if resp.status_code == 200:
            body = resp.json()
            if body.get("status") in ("success", "conflict"):
                resp.success()
                return True
        resp.failure(f"Login failed [{role_label}]: HTTP {resp.status_code} — {resp.text[:200]}")
        return False


def _logout(client, role_label: str) -> None:
    client.get("/logout.php", name=f"[{role_label}] GET /logout.php", allow_redirects=False)


def _get(client, path: str, role_label: str, name: str | None = None, **kwargs):
    """Convenience GET. Treats 400/403/404 as success — these are expected for
    auth-gated or state-dependent endpoints (e.g. lobby when quiz not in lobby state)."""
    label = name or f"[{role_label}] GET {path}"
    with client.get(path, name=label, catch_response=True, **kwargs) as resp:
        if resp.status_code in (200, 302, 400, 403, 404):
            resp.success()
        else:
            resp.failure(f"Unexpected HTTP {resp.status_code} for {path}")


def _post(client, path: str, role_label: str, payload: dict, name: str | None = None):
    label = name or f"[{role_label}] POST {path}"
    with client.post(path, json=payload, name=label, catch_response=True) as resp:
        if resp.status_code in (200, 302, 400, 403, 404):
            resp.success()
        else:
            resp.failure(f"Unexpected HTTP {resp.status_code} for {path}")


# ══════════════════════════════════════════════════════════════════════════════
#  STUDENT  –  Full sequential exam journey
# ══════════════════════════════════════════════════════════════════════════════

class StudentExamJourney(SequentialTaskSet):
    """
    Simulates the complete, ordered journey of a student sitting an exam.

    Steps:
      login → dashboard → lobby → exam page → fetch questions →
      save answers (~60 %) → poll status → log proctoring event →
      finish exam → results page → detailed results → export results → logout
    """

    attempt_id: int | None = None
    questions: list = []

    @task
    def t01_login(self):
        if not _login(self.client, STUDENT_EMAIL, STUDENT_PASSWORD, "Student"):
            self.interrupt(reschedule=False)

    @task
    def t02_dashboard(self):
        _get(self.client, "/views/student/dashboard.php", "Student")
        time.sleep(random.uniform(0.5, 1.5))

    @task
    def t03_lobby(self):
        _get(self.client, f"/views/student/lobby.php?id={QUIZ_ID}", "Student")
        time.sleep(random.uniform(0.5, 2.0))

    @task
    def t04_exam_page(self):
        _get(self.client, f"/views/student/exam.php?id={QUIZ_ID}", "Student")
        time.sleep(random.uniform(0.5, 1.0))

    @task
    def t05_fetch_questions(self):
        with self.client.get(
            f"/api/student/fetch_exam_questions.php?id={QUIZ_ID}",
            name="[Student] GET /api/student/fetch_exam_questions.php",
            catch_response=True,
        ) as resp:
            if resp.status_code == 200:
                body = resp.json()
                if "error" in body:
                    resp.failure(f"fetch_exam_questions error: {body['error']}")
                    self.interrupt(reschedule=False)
                    return
                self.attempt_id = body.get("attempt_id")
                self.questions  = body.get("questions", [])
                resp.success()
            elif resp.status_code == 500:
                # 'already completed' is an expected state on loop iteration 2+
                # (same student account re-running). Treat as success and skip exam.
                body = resp.json()
                if "already completed" in body.get("error", "").lower():
                    resp.success()
                    self.interrupt(reschedule=True)  # skip to logout, then restart
                else:
                    resp.failure(f"fetch_exam_questions HTTP 500: {resp.text[:200]}")
                    self.interrupt(reschedule=False)
                return
            else:
                resp.failure(f"fetch_exam_questions HTTP {resp.status_code}: {resp.text[:200]}")
                self.interrupt(reschedule=False)
        time.sleep(random.uniform(1.0, 2.0))

    @task
    def t06_save_answers(self):
        if not self.attempt_id or not self.questions:
            return

        to_answer = random.sample(self.questions, k=max(1, int(len(self.questions) * 0.6)))

        for question in to_answer:
            q_id   = question.get("id")
            q_type = question.get("question_type_id", 1)
            opts   = question.get("options", [])

            selected_ids: list = []
            answer_text: str   = ""

            if q_type == 1 and opts:               # MCQ — pick one
                selected_ids = [random.choice(opts)["id"]]
            elif q_type == 2 and opts:             # MSQ — pick 1–3
                k = random.randint(1, min(3, len(opts)))
                selected_ids = [o["id"] for o in random.sample(opts, k)]
            elif q_type == 3:                      # Descriptive
                answer_text = "Locust simulated descriptive answer."

            with self.client.post(
                "/api/student/save_answer.php",
                json={
                    "attempt_id": self.attempt_id,
                    "question_id": q_id,
                    "selected_option_ids": selected_ids,
                    "answer_text": answer_text,
                    "time_spent": random.randint(10, 120),
                },
                name="[Student] POST /api/student/save_answer.php",
                catch_response=True,
            ) as resp:
                if resp.status_code == 200 and resp.json().get("success"):
                    resp.success()
                else:
                    resp.failure(f"save_answer HTTP {resp.status_code}: {resp.text[:200]}")

            time.sleep(random.uniform(2.0, 8.0))  # realistic think-time

    @task
    def t07_poll_attempt_status(self):
        if not self.attempt_id:
            return
        _get(self.client, f"/api/student/get_attempt_status.php?id={self.attempt_id}", "Student")

    @task
    def t08_log_proctoring_event(self):
        if not self.attempt_id:
            return
        _post(self.client, "/api/student/log_event.php", "Student", {
            "attempt_id": self.attempt_id,
            "event_type": "TAB_SWITCH",
            "description": "Locust simulation: tab switch detected",
        })

    @task
    def t09_finish_exam(self):
        if not self.attempt_id:
            return
        with self.client.post(
            "/api/student/finish_exam.php",
            json={"attempt_id": self.attempt_id, "is_disqualified": False},
            name="[Student] POST /api/student/finish_exam.php",
            catch_response=True,
        ) as resp:
            if resp.status_code == 200 and resp.json().get("success"):
                resp.success()
            else:
                resp.failure(f"finish_exam HTTP {resp.status_code}: {resp.text[:200]}")

    @task
    def t10_results_page(self):
        _get(self.client, "/views/student/results.php", "Student")
        time.sleep(random.uniform(1.0, 2.0))

    @task
    def t11_detailed_results(self):
        if not self.attempt_id:
            return
        _get(self.client, f"/api/student/get_detailed_results.php?attempt_id={self.attempt_id}", "Student")
        _get(self.client, f"/views/student/detailed_results.php?attempt_id={self.attempt_id}", "Student")
        time.sleep(random.uniform(1.0, 3.0))

    @task
    def t12_export_results(self):
        if not self.attempt_id:
            return
        _get(self.client, f"/api/student/export_student_results.php?attempt_id={self.attempt_id}", "Student")

    @task
    def t13_disqualified_page(self):
        # Load the disqualified page (it will gracefully redirect/show empty if not disqualified)
        _get(self.client, "/views/student/disqualified.php", "Student")

    @task
    def t14_logout(self):
        _logout(self.client, "Student")
        self.attempt_id = None
        self.questions  = []
        self.interrupt(reschedule=True)


# ══════════════════════════════════════════════════════════════════════════════
#  FACULTY  –  Quiz management + live monitoring
# ══════════════════════════════════════════════════════════════════════════════

class FacultyBehavior(TaskSet):
    logged_in: bool = False

    def on_start(self):
        self.logged_in = _login(self.client, FACULTY_EMAIL, FACULTY_PASSWORD, "Faculty")

    def on_stop(self):
        if self.logged_in:
            _logout(self.client, "Faculty")

    # ── View pages ────────────────────────────────────────────────────────────
    @task(3)
    def dashboard(self):
        _get(self.client, "/views/faculty/dashboard.php", "Faculty")

    @task(3)
    def manage_quizzes(self):
        _get(self.client, "/views/faculty/manage_quizzes.php", "Faculty")

    @task(2)
    def start_quiz(self):
        _get(self.client, f"/views/faculty/start_quiz.php?id={QUIZ_ID}", "Faculty")

    @task(2)
    def view_quiz(self):
        _get(self.client, f"/views/faculty/view_quiz.php?id={QUIZ_ID}", "Faculty")

    @task(2)
    def reports(self):
        _get(self.client, f"/views/faculty/reports.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def item_analysis_page(self):
        _get(self.client, f"/views/faculty/item_analysis.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def evaluate_descriptive(self):
        _get(self.client, f"/views/faculty/evaluate_descriptive.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def evaluate_student(self):
        _get(self.client, f"/views/faculty/evaluate_student.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def display_questions(self):
        _get(self.client, f"/views/faculty/display_questions.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def question_view(self):
        _get(self.client, f"/views/faculty/question_view.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def how_to_use(self):
        _get(self.client, "/views/faculty/how_to_use.php", "Faculty")

    @task(1)
    def create_quiz_page(self):
        _get(self.client, "/views/faculty/create_quiz.php", "Faculty")

    @task(1)
    def edit_quiz_page(self):
        _get(self.client, f"/views/faculty/edit_quiz.php?id={QUIZ_ID}", "Faculty")

    @task(1)
    def edit_question_page(self):
        _get(self.client, f"/views/faculty/edit_question.php?id=1", "Faculty")

    # ── API endpoints ─────────────────────────────────────────────────────────
    @task(5)
    def live_monitoring(self):
        _get(self.client, f"/api/faculty/get_live_monitoring_data.php?quiz_id={QUIZ_ID}", "Faculty")

    @task(4)
    def get_lobby(self):
        # Correct param is ?id= (not ?quiz_id=)
        _get(self.client, f"/api/faculty/get_lobby_students.php?id={QUIZ_ID}", "Faculty")

    @task(3)
    def quiz_results_api(self):
        _get(self.client, f"/api/faculty/get_quiz_results.php?quiz_id={QUIZ_ID}", "Faculty")

    @task(2)
    def item_analysis_api(self):
        _get(self.client, f"/api/faculty/get_item_analysis.php?quiz_id={QUIZ_ID}", "Faculty")

    @task(1)
    def export_results(self):
        _get(self.client, f"/api/faculty/export_results.php?quiz_id={QUIZ_ID}", "Faculty")

    @task(1)
    def publish_results(self):
        # Safe: toggling publish state back to current won't corrupt data
        _post(self.client, "/api/faculty/publish_results.php", "Faculty",
              {"quiz_id": QUIZ_ID, "is_published": True})

    @task(1)
    def reenable_student(self):
        # Safe: attempt_id=0 will get a 400/404, we still measure response time
        _post(self.client, "/api/faculty/reenable_student.php", "Faculty",
              {"attempt_id": 0})

    @task(1)
    def update_quiz_status_read(self):
        # Correct param is ?id= (not ?quiz_id=)
        _get(self.client, f"/api/shared/get_quiz_status.php?id={QUIZ_ID}", "Faculty")


# ══════════════════════════════════════════════════════════════════════════════
#  ADMIN  –  Full management dashboard + read-heavy API
# ══════════════════════════════════════════════════════════════════════════════

class AdminBehavior(TaskSet):
    logged_in: bool = False

    def on_start(self):
        self.logged_in = _login(self.client, ADMIN_EMAIL, ADMIN_PASSWORD, "Admin")

    def on_stop(self):
        if self.logged_in:
            _logout(self.client, "Admin")

    # ── View pages ────────────────────────────────────────────────────────────
    @task(4)
    def dashboard(self):
        _get(self.client, "/views/admin/dashboard.php", "Admin")

    @task(3)
    def user_management(self):
        _get(self.client, "/views/admin/user_management.php", "Admin")

    @task(2)
    def manage_schools(self):
        _get(self.client, "/views/admin/manage_schools.php", "Admin")

    @task(2)
    def manage_courses(self):
        _get(self.client, "/views/admin/manage_courses.php", "Admin")

    @task(2)
    def batches(self):
        _get(self.client, "/views/admin/batches.php", "Admin")

    @task(2)
    def classes(self):
        _get(self.client, "/views/admin/classes.php", "Admin")

    @task(1)
    def electives(self):
        _get(self.client, "/views/admin/electives.php", "Admin")

    @task(1)
    def re_exam_groups(self):
        _get(self.client, "/views/admin/re_exam_groups.php", "Admin")

    @task(1)
    def exam_groups(self):
        _get(self.client, "/views/admin/exam_groups.php", "Admin")

    @task(1)
    def demote_students_page(self):
        _get(self.client, "/views/admin/demote_students.php", "Admin")

    @task(1)
    def cleanup_page(self):
        _get(self.client, "/views/admin/cleanup.php", "Admin")

    @task(1)
    def manage_roles(self):
        _get(self.client, "/views/admin/manage_roles.php", "Admin")

    @task(1)
    def upload_students_page(self):
        _get(self.client, "/views/admin/upload_students.php", "Admin")

    @task(1)
    def add_user_page(self):
        _get(self.client, "/views/admin/add_user.php", "Admin")

    @task(1)
    def edit_user_page(self):
        _get(self.client, "/views/admin/edit_user.php?id=1", "Admin")

    @task(1)
    def edit_class_page(self):
        _get(self.client, "/views/admin/edit_class.php?id=1", "Admin")

    @task(1)
    def manage_elective_page(self):
        _get(self.client, "/views/admin/manage_elective.php?id=1", "Admin")

    @task(1)
    def manage_re_exam_group_page(self):
        _get(self.client, "/views/admin/manage_re_exam_group.php?id=1", "Admin")

    # ── API endpoints ─────────────────────────────────────────────────────────
    @task(3)
    def dashboard_stats(self):
        _get(self.client, "/api/admin/get_dashboard_stats.php", "Admin")

    @task(2)
    def course_batches(self):
        _get(self.client, "/api/admin/get_course_batches.php?course_id=1", "Admin")

    @task(2)
    def search_student(self):
        _get(self.client, "/api/admin/search_student.php?q=test", "Admin")

    @task(1)
    def cleanup_preview(self):
        _get(self.client, "/api/admin/cleanup_preview.php", "Admin")

    @task(1)
    def students_for_demotion(self):
        _get(self.client, "/api/admin/get_students_for_demotion.php?course_id=1&year=1", "Admin")


# ══════════════════════════════════════════════════════════════════════════════
#  SHARED API  –  Called by multiple roles; tested under AdminBehavior too
# ══════════════════════════════════════════════════════════════════════════════

class SharedApiBehavior(TaskSet):
    logged_in: bool = False

    def on_start(self):
        # Use admin credentials since shared endpoints are role-agnostic
        self.logged_in = _login(self.client, ADMIN_EMAIL, ADMIN_PASSWORD, "Shared")
        if not self.logged_in:
            self.interrupt(reschedule=False)

    def on_stop(self):
        if self.logged_in:
            _logout(self.client, "Shared")

    @task(3)
    def quiz_status(self):
        # Correct param is ?id= (not ?quiz_id=)
        _get(self.client, f"/api/shared/get_quiz_status.php?id={QUIZ_ID}", "Shared")

    @task(2)
    def courses_by_school(self):
        _get(self.client, "/api/shared/get_courses_by_school.php?school_id=1", "Shared")

    @task(2)
    def batches_by_course(self):
        _get(self.client, "/api/shared/get_batches_by_course.php?course_id=1", "Shared")

    @task(2)
    def years_by_course(self):
        _get(self.client, "/api/shared/get_years_by_course.php?course_id=1", "Shared")

    @task(1)
    def groups_by_courses(self):
        _get(self.client, "/api/shared/get_groups_by_courses.php?course_id=1", "Shared")

    @task(1)
    def export_all_results(self):
        _get(self.client, "/api/shared/export_all_results.php", "Shared")

    @task(1)
    def change_password_page(self):
        _get(self.client, "/views/shared/change_password.php", "Shared")

    @task(1)
    def shared_dashboard(self):
        _get(self.client, "/views/shared/dashboard.php", "Shared")


# ══════════════════════════════════════════════════════════════════════════════
#  PLACECOM  –  Aggregated results
# ══════════════════════════════════════════════════════════════════════════════

class PlacecomBehavior(TaskSet):
    logged_in: bool = False

    def on_start(self):
        self.logged_in = _login(self.client, PLACECOM_EMAIL, PLACECOM_PASSWORD, "Placecom")
        if not self.logged_in:
            self.interrupt(reschedule=False)  # stop immediately — don't run tasks with bad creds

    def on_stop(self):
        if self.logged_in:
            _logout(self.client, "Placecom")

    @task(3)
    def dashboard(self):
        _get(self.client, "/views/placecom/dashboard.php", "Placecom")

    @task(3)
    def reports(self):
        _get(self.client, "/views/placecom/reports.php", "Placecom")

    @task(2)
    def all_quiz_results(self):
        _get(self.client, "/api/placecom/get_all_quiz_results.php", "Placecom")


# ══════════════════════════════════════════════════════════════════════════════
#  HEAD  –  Department head dashboard
# ══════════════════════════════════════════════════════════════════════════════

class HeadBehavior(TaskSet):
    logged_in: bool = False

    def on_start(self):
        self.logged_in = _login(self.client, HEAD_EMAIL, HEAD_PASSWORD, "Head")
        if not self.logged_in:
            self.interrupt(reschedule=False)  # stop immediately — don't run tasks with bad creds

    def on_stop(self):
        if self.logged_in:
            _logout(self.client, "Head")

    @task(1)
    def dashboard(self):
        _get(self.client, "/views/head/dashboard.php", "Head")


# ══════════════════════════════════════════════════════════════════════════════
#  UNAUTHENTICATED  –  Login page / static assets
# ══════════════════════════════════════════════════════════════════════════════

class UnauthenticatedBrowser(TaskSet):
    @task(5)
    def login_page(self):
        _get(self.client, "/login.php", "Anon")

    @task(1)
    def root_redirect(self):
        _get(self.client, "/", "Anon", allow_redirects=False)


# ══════════════════════════════════════════════════════════════════════════════
#  HttpUser classes — entry points for Locust
# ══════════════════════════════════════════════════════════════════════════════

class StudentUser(HttpUser):
    tasks     = [StudentExamJourney]
    weight    = 6
    wait_time = between(1, 5)
    host      = BASE_URL

class FacultyUser(HttpUser):
    tasks     = [FacultyBehavior]
    weight    = 3
    wait_time = between(2, 6)
    host      = BASE_URL

class AdminUser(HttpUser):
    tasks     = [AdminBehavior]
    weight    = 2
    wait_time = between(3, 8)
    host      = BASE_URL

class PlacecomUser(HttpUser):
    tasks     = [PlacecomBehavior]
    weight    = 1
    wait_time = between(3, 10)
    host      = BASE_URL

class HeadUser(HttpUser):
    tasks     = [HeadBehavior]
    weight    = 1
    wait_time = between(5, 12)
    host      = BASE_URL

class SharedApiUser(HttpUser):
    tasks     = [SharedApiBehavior]
    weight    = 1
    wait_time = between(2, 6)
    host      = BASE_URL


# ══════════════════════════════════════════════════════════════════════════════
#  Event hooks
# ══════════════════════════════════════════════════════════════════════════════

@events.request.add_listener
def on_request(request_type, name, response_time, response_length,
               response, context, exception, **kwargs):
    if response_time and response_time > SLOW_REQUEST_MS:
        print(f"[SLOW ⚠]  {request_type} {name}  →  {response_time:.0f} ms  (>{SLOW_REQUEST_MS} ms)")


@events.test_start.add_listener
def on_test_start(environment, **kwargs):
    print("=" * 60)
    print("  NMIMS Quiz App — Locust Full-Coverage Load Test")
    print(f"  Target  : {BASE_URL}")
    print(f"  Quiz ID : {QUIZ_ID}")
    print("=" * 60)


@events.test_stop.add_listener
def on_test_stop(environment, **kwargs):
    stats = environment.stats.total
    print("=" * 60)
    print("  NMIMS Quiz App — Load Test Complete")
    print(f"  Total requests  : {stats.num_requests}")
    print(f"  Failures        : {stats.num_failures}")
    print(f"  Median RT       : {stats.median_response_time} ms")
    print(f"  95th percentile : {stats.get_response_time_percentile(0.95)} ms")
    print(f"  99th percentile : {stats.get_response_time_percentile(0.99)} ms")
    print("=" * 60)
