# Admin Panel Documentation

## Overview
This admin panel provides a comprehensive interface for managing users, tokens, administrators, system settings, and monitoring system logs. It is designed for administrators to efficiently manage the F1 application.

## Features

### Dashboard
- Overview of key metrics: total users, active users, tokens purchased, and revenue
- Recent system activity logs

### User Management
- View all users with pagination and search functionality
- Add new users
- Edit existing user details
- Deactivate/activate user accounts
- Add tokens to user accounts
- Delete users (with confirmation)

### Token Management
- View all token packs
- Add new token packs
- Edit existing token packs
- Delete token packs (with confirmation)
- View token purchase history

### Admin Management
- View all admin users
- Add new admin users with different role levels
- Edit existing admin details
- Deactivate/activate admin accounts
- Delete admin users (with confirmation)



### Profile Management
- Update personal profile information
- Change password
- View account information

## Role Hierarchy

1. **Super Admin**
   - Full access to all features
   - Can manage other admin users
   - Can modify system settings

2. **Admin**
   - Access to user management, token management, and logs
   - Cannot manage other admin users or modify system settings

3. **Moderator**
   - Limited access to user management and logs
   - Cannot modify system settings or manage tokens

## Security Features

- Session-based authentication
- Password hashing using PHP's password_hash
- Role-based access control
- Action logging for all administrative actions
- CSRF protection on forms
- Input sanitization
- .htaccess protection for the admin directory

## File Structure

- `index.php` - Dashboard/home page
- `login.php` - Admin login page
- `logout.php` - Handles admin logout
- `users.php` - User management
- `tokens.php` - Token pack and purchase management
- `admins.php` - Admin user management
- `profile.php` - Admin profile management
- `.htaccess` - Directory security rules

## Database Tables Used

- `admin_users` - Stores admin account information
- `users` - Stores general user information
- `token_packs` - Available token packages
- `token_purchases` - Record of token purchases


## Installation

1. Ensure the database tables are created (see `database.sql`)
2. Make sure the admin directory is accessible only to authorized personnel
3. Configure proper file permissions
4. Access the admin panel via `/admin/login.php`
5. Default login credentials are in the database

## Development Notes

- The admin panel uses Bootstrap 5 for responsive design
- Font Awesome is used for icons
- Custom CSS is in `assets/css/admin.css`
- All forms include CSRF protection
- Input validation is performed on both client and server sides

## Maintenance

- Regularly review system logs for suspicious activity
- Periodically update admin passwords
- Keep PHP and server software updated
- Consider implementing additional security measures like 2FA in future versions