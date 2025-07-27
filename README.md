# NMIMS Quiz Application

A comprehensive web-based quiz application designed for educational institutions, featuring real-time monitoring, automated grading, and advanced proctoring capabilities.

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [User Roles](#user-roles)
- [Security Features](#security-features)
- [API Documentation](#api-documentation)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### For Students
- **Real-time Quiz Participation**: Join quiz lobbies and take exams in real-time
- **Multiple Question Types**: Support for MCQ, descriptive, and other question formats
- **Progress Tracking**: Monitor your quiz progress and submission status
- **Instant Results**: View results immediately after quiz completion (for auto-graded questions)
- **Secure Exam Environment**: Built-in proctoring features to ensure academic integrity

### For Faculty
- **Quiz Creation & Management**: Create, edit, and manage quizzes with ease
- **Bulk Question Upload**: Import questions via Excel spreadsheets
- **Real-time Monitoring**: Track student progress during live exams
- **Automated Grading**: Automatic grading for objective questions
- **Manual Evaluation**: Interface for grading descriptive answers
- **Comprehensive Reports**: Export results and generate detailed analytics
- **Exam Control**: Start, pause, or stop exams in real-time

### For Administrators
- **User Management**: Add, edit, and manage student and faculty accounts
- **System Overview**: Monitor all active quizzes and system usage
- **Access Control**: Manage permissions and user roles

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Libraries**: 
  - PHPSpreadsheet (Excel file handling)
  - Chart.js (Data visualization)
- **Security**: Session-based authentication, prepared statements for SQL injection prevention

## 📁 Project Structure

```
nmims_quiz_app/
├── api/                    # Backend PHP scripts for AJAX calls
│   ├── admin/             # Admin-specific API endpoints
│   ├── faculty/           # Faculty-specific API endpoints
│   ├── student/           # Student-specific API endpoints
│   ├── shared/            # Shared API endpoints
│   └── auth.php           # Authentication handler
├── assets/                # Static files
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Images and icons
├── config/               # Configuration files
│   ├── database.php      # Database connection
│   └── constants.php     # Application constants
├── lib/                  # External libraries
│   ├── phpspreadsheet/   # Excel handling
│   └── chartjs/          # Chart rendering
├── templates/            # Reusable PHP templates
│   ├── header.php
│   ├── footer.php
│   └── sidebars/         # Role-specific sidebars
├── views/                # User-facing pages
│   ├── admin/           # Admin dashboard and pages
│   ├── faculty/         # Faculty dashboard and pages
│   ├── student/         # Student dashboard and pages
│   └── shared/          # Shared views
├── index.php            # Application entry point
├── login.php            # Login page
├── logout.php           # Logout handler
└── .htaccess           # URL rewriting rules
```

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for PHP dependencies)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/aarushchaudhary/nm-quiz-app.git
   cd nm-quiz-app
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Create database**
   ```sql
   CREATE DATABASE nmims_quiz_db;
   ```

4. **Import database schema**
   ```bash
   mysql -u your_username -p nmims_quiz_db < database/schema.sql
   ```

5. **Configure database connection**
   - Copy `config/database.php.example` to `config/database.php`
   - Update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nmims_quiz_db');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

6. **Set proper permissions**
   ```bash
   chmod 755 -R nmims_quiz_app/
   chmod 777 uploads/  # If you have an uploads directory
   ```

7. **Configure web server**
   - Point your web server's document root to the `nmims_quiz_app` directory
   - Ensure mod_rewrite is enabled for Apache

## ⚙️ Configuration

### Application Settings
Edit `config/constants.php` to configure:
- Session timeout duration
- Maximum file upload size
- Quiz timer settings
- Proctoring strictness levels

### Email Configuration
Configure email settings for notifications:
```php
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_password');
```

## 📖 Usage

### First-time Setup
1. Access the application at `http://your-domain.com`
2. Log in with the default admin credentials:
   - Username: `admin`
   - Password: `admin123` (Change immediately!)
3. Create faculty and student accounts
4. Faculty can start creating quizzes

### Creating a Quiz (Faculty)
1. Log in with faculty credentials
2. Navigate to "Create Quiz"
3. Fill in quiz details:
   - Title and description
   - Time limit and attempt restrictions
   - Question shuffle settings
4. Add questions manually or upload via Excel
5. Save and publish the quiz

### Taking a Quiz (Student)
1. Log in with student credentials
2. View available quizzes on dashboard
3. Click "Join Lobby" for the desired quiz
4. Wait for faculty to start the exam
5. Complete all questions within the time limit
6. Submit the quiz

## 👥 User Roles

### Administrator
- Full system access
- User management (CRUD operations)
- System configuration
- View all quizzes and results

### Faculty
- Create and manage quizzes
- Monitor live exams
- Grade descriptive answers
- Generate reports
- Export results

### Student
- Take assigned quizzes
- View results and feedback
- Track quiz history

## 🔒 Security Features

- **Authentication**: Session-based authentication with secure password hashing
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based form submissions
- **Exam Proctoring**: 
  - Fullscreen enforcement
  - Tab switching detection
  - Copy-paste prevention
  - Right-click disabled during exams

## 📡 API Documentation

### Authentication
```
POST /api/auth.php
Parameters: username, password, role
Response: {success: boolean, message: string, user_data: object}
```

### Quiz Management (Faculty)
```
POST /api/faculty/create_quiz.php
GET /api/faculty/get_quizzes.php
POST /api/faculty/start_exam.php
GET /api/faculty/monitor_progress.php
```

### Student Actions
```
POST /api/student/join_lobby.php
GET /api/student/fetch_questions.php
POST /api/student/submit_answer.php
POST /api/student/finish_exam.php
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 for PHP code
- Use meaningful variable and function names
- Comment complex logic
- Write unit tests for new features

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- NMIMS for the project requirements
- PHPSpreadsheet contributors
- Chart.js team
- All contributors to this project

## 📞 Support

For issues and feature requests, please [create an issue](https://github.com/aarushchaudhary/nm-quiz-app/issues) on GitHub.

---

**Note**: This is an educational project. For production use, ensure proper security auditing and testing.