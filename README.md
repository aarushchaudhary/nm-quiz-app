/nmims_quiz_app/
|
|-- /api/                  # Contains all backend PHP scripts for AJAX calls
|   |-- /admin/
|   |   |-- manage_users.php
|   |-- /faculty/
|   |   |-- create_quiz.php
|   |   |-- upload_questions.php
|   |   |-- start_exam.php
|   |   |-- monitor_progress.php
|   |   |-- export_results.php
|   |   |-- evaluate_descriptive.php
|   |-- /student/
|   |   |-- join_lobby.php
|   |   |-- fetch_questions.php
|   |   |-- submit_answer.php
|   |   |-- log_event.php
|   |   |-- finish_exam.php
|   |-- /shared/
|   |   |-- get_quiz_details.php
|   |-- auth.php
|
|-- /assets/               # For all static files
|   |-- /css/              # All CSS stylesheets
|   |   |-- style.css      # Main stylesheet for all pages
|   |   |-- login.css
|   |   |-- dashboard.css
|   |   |-- exam.css
|   |-- /js/               # All JavaScript files
|   |   |-- main.js        # Common JS functions (e.g., AJAX helper)
|   |   |-- admin.js
|   |   |-- faculty.js
|   |   |-- student.js
|   |   |-- exam_proctoring.js # For fullscreen and tab-switching logic
|   |   |-- reports.js     # For chart/visualization rendering
|   |-- /images/           # Logos, icons, etc.
|   |-- /templates/        # Reusable HTML parts (header, footer)
|       |-- header.php
|       |-- footer.php
|       |-- sidebar_faculty.php
|       |-- sidebar_student.php
|
|-- /config/               # Database connection and core settings
|   |-- database.php       # PHP script for database connection (PDO or MySQLi)
|   |-- constants.php      # Defines constants for the application
|
|-- /lib/                  # External libraries
|   |-- /phpspreadsheet/   # For handling Excel file uploads/downloads
|   |-- /chartjs/          # For rendering charts and visualizations
|
|-- /views/                # All user-facing pages (the "V" in MVC)
|   |-- /admin/
|   |   |-- dashboard.php
|   |   |-- user_management.php
|   |-- /faculty/
|   |   |-- dashboard.php
|   |   |-- create_quiz.php
|   |   |-- manage_quizzes.php
|   |   |-- view_quiz.php
|   |   |-- real_time_monitor.php
|   |   |-- reports.php
|   |-- /student/
|   |   |-- dashboard.php
|   |   |-- lobby.php
|   |   |-- exam.php
|   |   |-- results.php
|   |-- /shared/
|       |-- reports_view.php
|
|-- login.php              # Main login page
|-- logout.php             # Script to handle user logout
|-- index.php              # Entry point, routes users based on session/role
|-- .htaccess              # For cleaner URLs (optional but recommended)
