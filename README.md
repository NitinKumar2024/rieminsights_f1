# RiemInsights – AI-Powered Data Analytics

This project implements a PHP-based authentication system for the RiemInsights platform, an AI-powered data analytics solution.

## Features

- User registration (signup)
- User login
- Password reset functionality
- Session management
- User dashboard

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP, WAMP, LAMP, or any PHP development environment

## Installation

1. Clone or download this repository to your web server directory (e.g., `htdocs` for XAMPP)
2. Create a MySQL database named `rieminsights`
3. Import the `database.sql` file into your MySQL database
4. Configure the database connection in `config.php` if needed
5. Access the application through your web browser (e.g., `http://localhost/f1`)

## Configuration

You can modify the following settings in the `config.php` file:

- Database connection details
- Site name and URL
- Email configuration for password reset

## Directory Structure

```
/f1
├── auth/
│   ├── login.php           # User login page
│   ├── signup.php          # User registration page
│   ├── forgot-password.php # Password reset request page
│   ├── reset-password.php  # Password reset confirmation page
│   └── logout.php          # User logout script
├── config.php              # Configuration and utility functions
├── index.php               # Dashboard/home page
├── database.sql            # Database schema
└── README.md               # This file
```

## Usage

1. **Registration**: Users can create a new account by providing their name, email, and password.
2. **Login**: Registered users can log in using their email and password.
3. **Password Reset**: Users who forget their password can request a reset link sent to their email.
4. **Dashboard**: After logging in, users are directed to the dashboard where they can see their account information.

## Security Features

- Password hashing using PHP's `password_hash()` function
- Input sanitization to prevent SQL injection
- CSRF protection through session management
- Secure password reset mechanism with expiring tokens

## Notes for Development

- The email functionality is commented out in the code. In a production environment, you should configure a proper email sending mechanism.
- For security reasons, update the default admin password in the database.
- Consider implementing additional security measures like rate limiting and HTTPS in a production environment.


## Contact

For any questions or support, please contact the RiemInsights team.