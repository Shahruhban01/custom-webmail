# MailFlow – PHP Webmail Dashboard

MailFlow is a modern **PHP-based webmail dashboard** designed for sending and managing emails using the native **PHP `mail()` function**.  
It provides a complete interface for composing emails, managing templates, handling attachments, and tracking email history.

The application includes:

- Secure authentication system
- Email composer with advanced options
- Email templates
- Sent mail history
- Draft management
- Attachment handling
- Scheduling support
- Responsive modern UI dashboard

The project is designed as a **lightweight internal webmail system** that does not rely on external mail libraries such as PHPMailer.

---

# Features

## Authentication System

The platform includes a secure user authentication system with:

- User registration
- Login
- Logout
- Password hashing using `password_hash`
- Password reset via token email
- Session-based authentication
- CSRF protection

Security best practices implemented:

- HTTP-only cookies
- Strict session handling
- Token-based password reset
- Input sanitization

---

## Email Composer

The compose module provides a complete email composition environment.

Supported fields:

- Sender Name
- Sender Email
- To
- CC
- BCC
- Subject
- Message body
- Attachments

Additional features:

- Email priority (Low / Normal / High)
- HTML email mode
- Draft saving
- Email scheduling
- Preview before sending
- Rich text toolbar

Attachments support:

- Multiple file uploads
- 5MB per file
- File type validation
- Secure upload directory

---

## Email Templates

Users can quickly compose emails using predefined templates.

Included templates:

- Internship certificate
- Offer letter
- Business email
- Notification email

Templates support placeholders such as:

```
[Name]
[Company]
[Position]
[Start Date]
```

Templates can be extended or modified from the database.

---

## Email History

The application records all sent emails in the database.

Stored information:

- Sender name
- Sender email
- Receiver email
- CC / BCC
- Subject
- Message
- Attachments
- Status
- Timestamp

Email status types:

- Sent
- Failed
- Draft
- Scheduled

Users can:

- View email details
- Track sending status
- Browse previous emails

---

## Dashboard Interface

The UI is designed to resemble modern SaaS dashboards such as:

- Gmail
- ProtonMail
- Linear

Dashboard components include:

Sidebar navigation:

- Compose
- Sent Mail
- Drafts
- Attachments
- Templates
- Settings

Main dashboard statistics:

- Total emails
- Sent emails
- Drafts
- Failed emails

Recent email list with quick access.

---

# Technology Stack

Backend:

```
PHP 8+
MySQL / MariaDB
PDO database connection
Native PHP mail()
```

Frontend:

```
HTML5
CSS3
Vanilla JavaScript
Responsive dashboard layout
```

Design system includes:

- Inter typography
- CSS variables
- Component-based styling
- Responsive breakpoints

---

# Project Structure

```
/webmail
│
├── index.php
├── config.php
├── mailer.php
├── database.sql
│
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   └── logout.php
│
├── dashboard/
│   ├── index.php
│   ├── compose.php
│   ├── sent.php
│   ├── view_email.php
│   ├── drafts.php
│   ├── templates.php
│   └── settings.php
│
├── components/
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
│
├── uploads/
│   └── .htaccess
│
└── assets/
    ├── css/
    ├── js/
    └── images/
```

---

# Installation

## Requirements

Server requirements:

```
PHP 8.0+
MySQL 5.7+
Apache or Nginx
Sendmail or SMTP configured
```

---

## Step 1: Clone or Upload Project

Upload the project folder to your web server.

Example path:

```
/var/www/html/webmail
```

---

## Step 2: Create Database

Open phpMyAdmin or MySQL CLI and run:

```
database.sql
```

This creates:

- users table
- emails table
- templates table
- password reset tokens

---

## Step 3: Configure Database

Edit:

```
config.php
```

Update database credentials:

```
DB_HOST
DB_USER
DB_PASS
DB_NAME
```

---

## Step 4: Configure Mail Server

MailFlow uses the native PHP `mail()` function.

Your server must support:

```
sendmail
postfix
exim
```

Example Linux configuration:

```
sudo apt install postfix
```

Test mail sending:

```php
mail("test@example.com","Test","MailFlow test message");
```

---

## Step 5: Set Upload Permissions

Ensure the upload directory is writable:

```
chmod 755 uploads
```

Or:

```
chmod 775 uploads
```

---

## Step 6: Access Application

Open in browser:

```
http://localhost/webmail
```

Register a new account and begin using the system.

---

# Security Measures

MailFlow implements several security protections.

## CSRF Protection

All forms include CSRF tokens.

```
csrfToken()
validateCsrf()
```

---

## Password Security

Passwords are stored using:

```
password_hash()
password_verify()
```

---

## File Upload Protection

Uploads are protected via:

- extension whitelist
- size limits
- `.htaccess` blocking script execution

---

## Input Sanitization

User inputs are sanitized using:

```
htmlspecialchars
filter_var
```

---

# Email Sending Engine

The core mail logic resides in:

```
mailer.php
```

Capabilities:

- MIME multipart support
- Attachments
- HTML emails
- CC / BCC
- Priority headers

Example:

```php
sendMail([
  'sender_name' => 'John',
  'sender_email' => 'john@example.com',
  'to' => 'user@example.com',
  'subject' => 'Test Email',
  'message' => 'Hello world'
]);
```

---

# Scheduling Emails

Emails can be scheduled for future sending.

If `scheduled_at` is set and the time is in the future, the email status becomes:

```
scheduled
```

A cron worker can later process scheduled emails.

Example cron job:

```
php scheduler.php
```

---

# Customization

You can customize:

- Templates
- CSS theme
- Dashboard layout
- Email headers
- Sender branding

Modify files inside:

```
assets/css
components
dashboard
```

---

# Limitations

Because the system uses **native PHP mail()**, some limitations exist:

- Emails may go to spam without SPF/DKIM
- Limited deliverability compared to SMTP
- No built-in bounce tracking

For production environments, consider switching to SMTP.

---

# Future Improvements

Possible enhancements:

- SMTP support
- Email queue worker
- Draft autosave
- Rich text editor upgrade
- Email tracking
- Multi-user teams
- Admin dashboard
- API endpoints
- Flutter mobile app

---

# License

This project is open-source and may be used for:

- educational purposes
- internal tools
- prototype systems

Modify and extend as needed.

---

# Author

Developed as a **modern lightweight webmail dashboard** using pure PHP and native mail sending.

```
MailFlow Webmail System
```

---