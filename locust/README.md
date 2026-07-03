# NMIMS Quiz App — Locust Load Tests

End-to-end load and performance tests for the NMIMS Quiz App, built with **[Locust](https://locust.io/)**.

---

## Directory Structure

```
locust/
├── locustfile.py      ← Main test file (all user scenarios)
├── requirements.txt   ← Python dependencies (locust, faker)
├── setup.sh           ← One-command environment bootstrap
└── README.md          ← This file
```

---

## Quick Start

### 1. Bootstrap the environment

```bash
cd locust
bash setup.sh
```

This creates a `.venv/` directory, installs `locust` and dependencies, and prints usage instructions.

### 2. Activate the virtual environment

```bash
source .venv/bin/activate
```

### 3. Configure credentials

The test reads settings from environment variables (sensible defaults are provided):

| Variable | Default | Description |
|---|---|---|
| `QUIZ_BASE_URL` | `http://localhost:8080` | Base URL of the running PHP app |
| `STUDENT_EMAIL` | `student@nmims.edu` | Test student account email |
| `STUDENT_PASSWORD` | `password123` | Test student password |
| `FACULTY_EMAIL` | `faculty@nmims.edu` | Test faculty account email |
| `FACULTY_PASSWORD` | `password123` | Test faculty password |
| `ADMIN_EMAIL` | `admin@nmims.edu` | Test admin account email |
| `ADMIN_PASSWORD` | `password123` | Test admin password |
| `PLACECOM_EMAIL` | `placecom@nmims.edu` | Test placecom officer email |
| `PLACECOM_PASSWORD` | `password123` | Test placecom password |
| `QUIZ_ID` | `1` | ID of the quiz used by students |

Export them before running:

```bash
export QUIZ_BASE_URL=http://localhost:8080
export STUDENT_EMAIL=student@nmims.edu
export STUDENT_PASSWORD=yourpassword
export FACULTY_EMAIL=faculty@nmims.edu
export FACULTY_PASSWORD=yourpassword
export QUIZ_ID=1
```

### 4. Start Locust

**Web UI** (visit http://localhost:8089 to control the test interactively):

```bash
locust -f locustfile.py
```

**Headless** (automated, no browser needed):

```bash
# 50 users, ramp up 5/s, run for 2 minutes, save CSV results
locust -f locustfile.py --headless -u 50 -r 5 -t 2m \
       --csv=results/run_$(date +%s)
```

---

## User Scenarios

### `StudentUser` (weight: 6 — highest load)

Performs the **complete sequential exam journey**:

1. `POST /api/auth.php` — Login (force=true to bypass session conflicts)
2. `GET /views/student/dashboard.php` — Load student dashboard
3. `GET /api/student/fetch_exam_questions.php?id=<QUIZ_ID>` — Create/resume attempt
4. `POST /api/student/save_answer.php` — Save answers (~60% of questions, realistic pauses)
5. `GET /api/student/get_attempt_status.php` — Poll proctoring lock status
6. `POST /api/student/log_event.php` — Simulate tab-switch proctoring event
7. `POST /api/student/finish_exam.php` — Submit exam
8. `GET /api/student/get_detailed_results.php` — View results
9. `GET /logout.php` — Logout

### `FacultyUser` (weight: 2)

Simulates monitoring activity (polling-heavy):

- Dashboard, live monitoring, lobby list, quiz results, item analysis

### `AdminUser` (weight: 1)

Read-heavy browsing:

- Dashboard, user list, school management, course management

### `PlacecomUser` (weight: 1)

- Dashboard, results page

---

## Performance Thresholds

The test prints a warning in stdout for any request exceeding **2000 ms**:

```
[SLOW ⚠] GET [Student] /api/student/fetch_exam_questions.php took 2345 ms
```

A test summary is printed when the run finishes:

```
============================================================
  NMIMS Quiz App — Load Test Complete
  Total requests  : 4820
  Failures        : 3
  Median RT       : 142 ms
  95th percentile : 890 ms
============================================================
```

---

## Tips

- **Run the PHP server first**: `php -S localhost:8080 router.php` from the project root.
- **Use a real test DB**: Never point this at production. Use a seeded test database.
- **Use `--csv`**: Save CSV results for later analysis or CI integration.
- **Adjust weights**: Edit the `weight` attribute on each `HttpUser` class to change the user-type ratio.
- **Tune `wait_time`**: `between(1, 5)` simulates realistic think-time. Lower values = higher stress.
