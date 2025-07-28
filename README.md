# NMIMS Quiz Application

A comprehensive web-based quiz application designed for NMIMS (Narsee Monjee Institute of Management Studies), featuring real-time exam monitoring, automated grading, multi-format question support, and advanced proctoring capabilities.

## 🎯 Overview

The NMIMS Quiz Application is a full-featured examination platform that enables faculty to create and manage online quizzes while providing students with a secure and intuitive interface for taking exams. The system includes real-time monitoring, automated grading for objective questions, and comprehensive reporting features.

## ✨ Key Features

### 👨‍🎓 Student Portal
- **Secure Authentication**: Role-based login system with session management
- **Quiz Lobby System**: Real-time waiting room for synchronized exam starts
- **Multiple Question Formats**: 
  - Multiple Choice Questions (MCQ)
  - Descriptive/Essay questions
  - True/False questions
  - Fill in the blanks
- **Exam Proctoring**: 
  - Fullscreen enforcement during exams
  - Tab/window switching detection and logging
  - Copy-paste prevention
  - Right-click disabled
- **Real-time Progress Saving**: Auto-save answers to prevent data loss
- **Instant Results**: View scores immediately for auto-graded questions
- **Answer Review**: Review submitted answers after exam completion

### 👨‍🏫 Faculty Portal
- **Quiz Management**:
  - Create quizzes with multiple sections
  - Set time limits and attempt restrictions
  - Configure question shuffling and randomization
  - Schedule quizzes with start/end times
- **Question Bank**:
  - Add questions individually or bulk upload via Excel
  - Categorize questions by topic/difficulty
  - Reuse questions across multiple quizzes
- **Live Monitoring**:
  - Real-time dashboard showing student progress
  - View submission status and time remaining
  - Monitor suspicious activities (tab switches, etc.)
- **Grading System**:
  - Automated grading for objective questions
  - Manual evaluation interface for descriptive answers
  - Partial marking support
  - Grade export functionality
- **Analytics & Reports**:
  - Detailed performance analytics
  - Question-wise analysis
  - Class performance comparison
  - Export results to Excel/CSV

### 👨‍💼 Administrator Panel
- **User Management**:
  - Bulk user creation via Excel upload
  - Role assignment and permissions
  - Password reset functionality
  - User activity logs
- **System Monitoring**:
  - Active quiz overview
  - System resource usage
  - Error logs and debugging tools
- **Configuration**:
  - Global settings management
  - Email notification setup
  - Backup and restore functionality

## 🛠️ Technology Stack

### Frontend
- **HTML5**: Semantic markup for better accessibility
- **CSS3**: Modern styling with responsive design
- **JavaScript (ES6+)**: Interactive features and AJAX calls
- **AJAX**: Asynchronous data loading for better UX

### Backend
- **PHP 7.4+**: Server-side logic and API endpoints
- **MySQL/MariaDB**: Relational database for data storage
- **PDO**: Secure database interactions with prepared statements

### Libraries & Frameworks
- **PHPSpreadsheet**: Excel file import/export functionality
- **Chart.js**: Data visualization for analytics
- **jQuery**: DOM manipulation and AJAX (if used)
- **Bootstrap**: Responsive UI components (if used)

## 📁 Detailed Project Structure

```
nm-quiz-app/
├── api/                          # Backend API endpoints
│   ├── admin/                    # Administrator endpoints
│   │   ├── manage_users.php      # User CRUD operations
│   │   ├── system_config.php     # System settings
│   │   └── activity_logs.php     # User activity tracking
│   ├── faculty/                  # Faculty endpoints
│   │   ├── create_quiz.php       # Quiz creation
│   │   ├── upload_questions.php  # Bulk question upload
│   │   ├── start_exam.php        # Exam control
│   │   ├── monitor_progress.php  # Live monitoring
│   │   ├── evaluate_descriptive.php # Manual grading
│   │   └── export_results.php    # Result export
│   ├── student/                  # Student endpoints
│   │   ├── join_lobby.php        # Join exam lobby
│   │   ├── fetch_questions.php   # Get quiz questions
│   │   ├── submit_answer.php     # Save answers
│   │   ├── log_event.php         # Log proctoring events
│   │   └── finish_exam.php       # Submit final exam
│   ├── shared/                   # Common endpoints
│   │   ├── get_quiz_details.php  # Quiz information
│   │   └── get_results.php       # Result retrieval
│   └── auth.php                  # Authentication handler
│
├── assets/                       # Static resources
│   ├── css/                      # Stylesheets
│   │   ├── style.css            # Global styles
│   │   ├── login.css            # Login page styles
│   │   ├── dashboard.css        # Dashboard styles
│   │   ├── exam.css             # Exam interface styles
│   │   └── responsive.css       # Mobile responsiveness
│   ├── js/                      # JavaScript files
│   │   ├── main.js              # Common functions
│   │   ├── admin.js             # Admin functionality
│   │   ├── faculty.js           # Faculty features
│   │   ├── student.js           # Student features
│   │   ├── exam_proctoring.js   # Proctoring logic
│   │   ├── timer.js             # Exam timer
│   │   └── charts.js            # Chart rendering
│   └── images/                  # Images and icons
│       ├── logo.png             # Application logo
│       └── icons/               # UI icons
│
├── config/                      # Configuration files
│   ├── database.php             # Database connection
│   ├── constants.php            # Application constants
│   └── functions.php            # Helper functions
│
├── database/                    # Database files
│   ├── schema.sql              # Database structure
│   ├── seed_data.sql           # Initial data
│   └── migrations/             # Database updates
│
├── lib/                        # Third-party libraries
│   ├── phpspreadsheet/         # Excel handling
│   ├── phpmailer/              # Email functionality
│   └── chartjs/                # Chart library
│
├── templates/                  # Reusable PHP components
│   ├── header.php              # Common header
│   ├── footer.php              # Common footer
│   ├── navbar.php              # Navigation bar
│   ├── sidebar_admin.php       # Admin sidebar
│   ├── sidebar_faculty.php     # Faculty sidebar
│   └── sidebar_student.php     # Student sidebar
│
├── uploads/                    # File uploads directory
│   ├── questions/              # Question attachments
│   ├── profiles/               # User profile pictures
│   └── temp/                   # Temporary files
│
├── views/                      # User interface pages
│   ├── admin/                  # Admin pages
│   │   ├── dashboard.php       # Admin dashboard
│   │   ├── users.php           # User management
│   │   └── reports.php         # System reports
│   ├── faculty/                # Faculty pages
│   │   ├── dashboard.php       # Faculty dashboard
│   │   ├── create_quiz.php     # Quiz creation form
│   │   ├── manage_quizzes.php  # Quiz list
│   │   ├── monitor_exam.php    # Live monitoring
│   │   └── grade_answers.php   # Grading interface
│   ├── student/                # Student pages
│   │   ├── dashboard.php       # Student dashboard
│   │   ├── lobby.php           # Exam waiting room
│   │   ├── exam.php            # Exam interface
│   │   └── results.php         # Result view
│   └── shared/                 # Shared pages
│       └── profile.php         # User profile
│
├── .htaccess                   # Apache configuration
├── .gitignore                  # Git ignore file
├── composer.json               # PHP dependencies
├── index.php                   # Application entry point
├── login.php                   # Login page
├── logout.php                  # Logout handler
├── README.md                   # Project documentation
└── LICENSE                     # MIT License

```

## 🚀 Installation Guide

### Prerequisites
- **Web Server**: Apache 2.4+ with mod_rewrite enabled or Nginx
- **PHP**: Version 7.4 or higher with extensions:
  - PDO_MySQL
  - JSON
  - Session
  - FileInfo
  - GD (for image processing)
  - ZIP (for Excel handling)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Composer**: For managing PHP dependencies

### Step-by-Step Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/aarushchaudhary/nm-quiz-app.git
   cd nm-quiz-app
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE nmims_quiz_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Import schema
   mysql -u root -p nmims_quiz_db < database/schema.sql
   
   # Import seed data (optional)
   mysql -u root -p nmims_quiz_db < database/seed_data.sql
   ```

4. **Configure Application**
   ```bash
   # Copy example configuration
   cp config/database.php.example config/database.php
   
   # Edit database configuration
   nano config/database.php
   ```
   
   Update the following in `config/database.php`:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nmims_quiz_db');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_CHARSET', 'utf8mb4');
   ```

5. **Set Directory Permissions**
   ```bash
   # Set appropriate permissions
   chmod -R 755 .
   chmod -R 777 uploads/
   chmod -R 777 uploads/questions/
   chmod -R 777 uploads/profiles/
   chmod -R 777 uploads/temp/
   ```

6. **Configure Web Server**

   **For Apache:**
   Create/edit `.htaccess` in the root directory:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
   
   # Security headers
   Header set X-Frame-Options "SAMEORIGIN"
   Header set X-Content-Type-Options "nosniff"
   Header set X-XSS-Protection "1; mode=block"
   ```

   **For Nginx:**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   
   location ~ \.php$ {
       fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
       fastcgi_index index.php;
       include fastcgi_params;
   }
   ```

7. **Initial Setup**
   - Navigate to `http://your-domain.com`
   - Login with default admin credentials:
     - Username: `admin`
     - Password: `Admin@123`
   - **Important**: Change the admin password immediately!

## ⚙️ Configuration

### Application Settings (`config/constants.php`)
```php
// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'NMIMS_QUIZ_SESSION');

// Upload Limits
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Quiz Settings
define('DEFAULT_QUIZ_DURATION', 60); // minutes
define('QUESTION_AUTO_SAVE_INTERVAL', 30); // seconds
define('MAX_TAB_SWITCHES', 3); // before warning

// Proctoring Settings
define('ENABLE_FULLSCREEN', true);
define('ENABLE_TAB_DETECTION', true);
define('ENABLE_COPY_PROTECTION', true);
define('LOG_PROCTORING_EVENTS', true);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'noreply@nmims.edu');
define('FROM_NAME', 'NMIMS Quiz System');
```

### Database Tables Overview
- `users` - User accounts with roles
- `quizzes` - Quiz metadata
- `questions` - Question bank
- `quiz_questions` - Quiz-question mapping
- `answers` - Student answers
- `results` - Quiz results
- `proctoring_logs` - Security event logs
- `activity_logs` - User activity tracking

## 📱 Usage Guide

### For Administrators

1. **User Management**
   - Navigate to Admin Dashboard → Users
   - Add users individually or bulk upload via Excel
   - Excel format: `Name, Email, Role, Password`

2. **System Monitoring**
   - View real-time system statistics
   - Monitor active quizzes and participants
   - Check error logs for troubleshooting

### For Faculty

1. **Creating a Quiz**
   - Go to Faculty Dashboard → Create Quiz
   - Fill in quiz details:
     - Title and description
     - Duration and attempts allowed
     - Start and end date/time
     - Passing percentage
   - Add questions:
     - Manual entry for individual questions
     - Excel upload for bulk import
     - Set marks and negative marking

2. **Managing Live Exams**
   - Open Monitor Exam page during quiz
   - View real-time student progress
   - Handle technical issues
   - Stop/extend exam if needed

3. **Grading**
   - Auto-graded MCQs appear instantly
   - Review descriptive answers
   - Provide feedback and partial marks
   - Export results to Excel

### For Students

1. **Taking a Quiz**
   - View available quizzes on dashboard
   - Click "Attempt Quiz"
   - Join the lobby and wait for start
   - Complete all questions
   - Review before final submission

2. **During Exam**
   - Timer shows remaining time
   - Navigation panel shows question status
   - Auto-save prevents data loss
   - Flag questions for review

## 🔒 Security Features

### Authentication & Authorization
- Secure password hashing using `password_hash()`
- Session-based authentication with timeout
- Role-based access control (RBAC)
- CSRF token protection on forms

### Exam Security
- **Proctoring Features**:
  - Fullscreen mode enforcement
  - Browser tab/window switch detection
  - Right-click context menu disabled
  - Text selection and copy disabled
  - Developer tools detection
  - Print screen prevention (limited)

### Data Security
- SQL injection prevention via prepared statements
- XSS protection through output escaping
- File upload validation and sanitization
- Secure session configuration
- HTTPS enforcement recommended

## 📊 API Documentation

### Authentication Endpoints

#### Login
```http
POST /api/auth.php
Content-Type: application/json

{
    "username": "student@nmims.edu",
    "password": "password123",
    "role": "student"
}

Response:
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user_id": 1,
        "name": "John Doe",
        "role": "student",
        "session_token": "..."
    }
}
```

### Student API Endpoints

#### Get Available Quizzes
```http
GET /api/student/get_quizzes.php
Authorization: Bearer {session_token}

Response:
{
    "success": true,
    "quizzes": [
        {
            "quiz_id": 1,
            "title": "Midterm Exam",
            "duration": 60,
            "total_marks": 100,
            "start_time": "2024-01-15 10:00:00",
            "end_time": "2024-01-15 12:00:00",
            "attempts_allowed": 1,
            "attempts_used": 0
        }
    ]
}
```

#### Submit Answer
```http
POST /api/student/submit_answer.php
Authorization: Bearer {session_token}
Content-Type: application/json

{
    "quiz_id": 1,
    "question_id": 5,
    "answer": "Option A",
    "time_taken": 45
}
```

### Faculty API Endpoints

#### Create Quiz
```http
POST /api/faculty/create_quiz.php
Authorization: Bearer {session_token}
Content-Type: application/json

{
    "title": "Final Exam",
    "description": "End semester examination",
    "duration": 120,
    "total_marks": 100,
    "passing_marks": 40,
    "negative_marking": true,
    "shuffle_questions": true,
    "show_results": "after_evaluation",
    "start_time": "2024-01-20 10:00:00",
    "end_time": "2024-01-20 14:00:00"
}
```

#### Monitor Progress
```http
GET /api/faculty/monitor_progress.php?quiz_id=1
Authorization: Bearer {session_token}

Response:
{
    "success": true,
    "participants": [
        {
            "student_id": 1,
            "name": "John Doe",
            "start_time": "10:05:23",
            "progress": "15/25 questions",
            "status": "in_progress",
            "tab_switches": 0,
            "time_remaining": "45:30"
        }
    ]
}
```

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Quiz creation with various question types
- [ ] Excel upload functionality
- [ ] Student exam flow
- [ ] Proctoring features
- [ ] Result calculation
- [ ] Report generation
- [ ] Mobile responsiveness

### Automated Testing (Future Enhancement)
```bash
# Run PHP unit tests
./vendor/bin/phpunit tests/

# Run JavaScript tests
npm test
```

## 🚦 Troubleshooting

### Common Issues

1. **"Access Denied" Error**
   - Check file permissions
   - Verify `.htaccess` configuration
   - Ensure mod_rewrite is enabled

2. **Database Connection Failed**
   - Verify database credentials
   - Check if MySQL service is running
   - Ensure database exists

3. **Excel Upload Not Working**
   - Check PHP upload_max_filesize
   - Verify PHPSpreadsheet is installed
   - Check file permissions on uploads/

4. **Session Timeout Issues**
   - Adjust SESSION_LIFETIME in constants.php
   - Check PHP session.gc_maxlifetime
   - Verify session save path permissions

5. **Email Notifications Not Sending**
   - Verify SMTP credentials
   - Check firewall settings
   - Enable "Less secure app access" for Gmail

## 🤝 Contributing

We welcome contributions from the community! Please follow these guidelines:

### Development Workflow
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Make your changes
4. Write/update tests
5. Commit changes (`git commit -m 'Add AmazingFeature'`)
6. Push to branch (`git push origin feature/AmazingFeature`)
7. Create a Pull Request

### Coding Standards
- **PHP**: Follow PSR-12 coding standard
- **JavaScript**: Use ESLint configuration
- **Database**: Use meaningful table/column names
- **Comments**: Write clear, concise comments
- **Git**: Write descriptive commit messages

### Pull Request Guidelines
- Describe the changes clearly
- Reference any related issues
- Include screenshots for UI changes
- Ensure all tests pass
- Update documentation as needed

## 📈 Future Enhancements

- [ ] AI-powered question generation
- [ ] Video proctoring integration
- [ ] Mobile app development
- [ ] Advanced analytics dashboard
- [ ] Question bank sharing between faculty
- [ ] Integration with LMS systems
- [ ] Multi-language support
- [ ] Offline exam capability
- [ ] Blockchain-based certificate generation

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Team

- **Project Lead**: Aarush Chaudhary
- **Contributors**: [List of contributors]

## 🙏 Acknowledgments

- NMIMS for project requirements and support
- PHPSpreadsheet team for Excel handling library
- Chart.js contributors for visualization tools
- Open source community for various libraries used
- Beta testers and early adopters

## 📞 Support

For support, please use the following channels:

- **Issues**: [GitHub Issues](https://github.com/aarushchaudhary/nm-quiz-app/issues)
- **Documentation**: [Wiki](https://github.com/aarushchaudhary/nm-quiz-app/wiki)

---

**Note**: This is an educational project. For production deployment, ensure proper security auditing, penetration testing, and compliance with data protection regulations.

**Version**: 1.0.0  
**Last Updated**: January 2024