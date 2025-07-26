-- ================================
-- 1. Creation of Database
-- ================================
CREATE DATABASE IF NOT EXISTS nmims_quiz_app;
USE nmims_quiz_app;

-- ================================
-- 2. Lookup Tables (for categories)
-- ================================
CREATE TABLE roles (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE    -- e.g. 'admin','faculty','placement','student'
);

CREATE TABLE schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE -- e.g., 'STME', 'SPTM', 'SOC', 'SOL', 'SBM'
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

CREATE TABLE question_types (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE    -- e.g. 'mcq','multiple_answer','descriptive'
);

CREATE TABLE question_difficulties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL UNIQUE -- e.g., 'easy', 'medium', 'hard'
);

CREATE TABLE exam_statuses (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE    -- e.g. 'draft','scheduled','running','completed', 'cancelled'
);

-- ================================
-- 3. Core User & Profile Tables
-- ================================
CREATE TABLE users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  username       VARCHAR(50)  NOT NULL UNIQUE, -- Can be SAP ID
  password_hash  VARCHAR(255) NOT NULL,
  role_id        INT          NOT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE students (
  user_id         INT           PRIMARY KEY,
  name            VARCHAR(100)  NOT NULL,
  sap_id          VARCHAR(20)   NOT NULL UNIQUE,
  roll_no         VARCHAR(20)   NOT NULL UNIQUE,
  course_id       INT           NOT NULL,
  batch           VARCHAR(50)   NOT NULL,
  graduation_year YEAR          NOT NULL,
  FOREIGN KEY (user_id)   REFERENCES users(id),
  FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE faculties (
  user_id    INT          PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  sap_id     VARCHAR(20)  NOT NULL UNIQUE,
  department VARCHAR(100),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE placement_officers (
  user_id    INT          PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  sap_id     VARCHAR(20)  NOT NULL UNIQUE,
  department VARCHAR(100),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE admins (
  user_id    INT          PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ================================
-- 4. Exams & Questions
-- ================================
CREATE TABLE quizzes (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  title               VARCHAR(150) NOT NULL,
  faculty_id          INT          NOT NULL,    -- creator
  course_id           INT          NOT NULL,
  start_time          DATETIME     NOT NULL,
  end_time            DATETIME     NOT NULL,
  duration_minutes    INT          NOT NULL,
  status_id           INT          NOT NULL,
  config_easy_count   INT          NOT NULL DEFAULT 0,
  config_medium_count INT          NOT NULL DEFAULT 0,
  config_hard_count   INT          NOT NULL DEFAULT 0,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (faculty_id) REFERENCES faculties(user_id),
  FOREIGN KEY (course_id)  REFERENCES courses(id),
  FOREIGN KEY (status_id)  REFERENCES exam_statuses(id)
);

CREATE TABLE questions (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id             INT            NOT NULL,
  question_type_id    INT            NOT NULL,
  difficulty_id       INT            NOT NULL,
  question_text       TEXT           NOT NULL,
  points              DECIMAL(5,2)   NOT NULL DEFAULT 1.00,
  created_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quiz_id)          REFERENCES quizzes(id) ON DELETE CASCADE,
  FOREIGN KEY (question_type_id) REFERENCES question_types(id),
  FOREIGN KEY (difficulty_id)    REFERENCES question_difficulties(id)
);

CREATE TABLE options (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  question_id  INT           NOT NULL,
  option_text  TEXT          NOT NULL,
  is_correct   BOOLEAN       NOT NULL DEFAULT FALSE,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- ================================
-- 5. Student Attempts & Answers
-- ================================
CREATE TABLE student_attempts (
  id                     INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id                INT         NOT NULL,
  student_id             INT         NOT NULL,
  started_at             TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at           TIMESTAMP   NULL,
  total_score            DECIMAL(5,2),
  is_disqualified        BOOLEAN     NOT NULL DEFAULT FALSE,
  can_resume             BOOLEAN     NOT NULL DEFAULT TRUE,
  UNIQUE(quiz_id, student_id), -- A student gets one attempt per quiz
  FOREIGN KEY (quiz_id)    REFERENCES quizzes(id),
  FOREIGN KEY (student_id) REFERENCES students(user_id)
);

CREATE TABLE student_answers (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id   INT NOT NULL,
  question_id  INT NOT NULL,
  answer_text  TEXT,              -- for descriptive questions
  selected_option_ids VARCHAR(255), -- for MCQs, comma-separated IDs for multi-select
  is_correct   BOOLEAN,           -- Can be pre-calculated for MCQs
  score_awarded DECIMAL(5,2),
  time_spent_seconds INT,
  FOREIGN KEY (attempt_id)  REFERENCES student_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- ================================
-- 6. Logging and Monitoring
-- ================================
CREATE TABLE event_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id  INT,
  user_id     INT,
  event_type  VARCHAR(100) NOT NULL, -- e.g., 'LOGIN', 'QUIZ_START', 'ALT_TAB_WARNING', 'QUIZ_SUBMIT', 'DISQUALIFIED'
  description TEXT,
  ip_address  VARCHAR(45),
  timestamp   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attempt_id) REFERENCES student_attempts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
