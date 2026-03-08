-- -- Run this in phpMyAdmin or MySQL CLI
-- CREATE DATABASE IF NOT EXISTS webmail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE webmail_db;

-- Users table
CREATE TABLE webmail_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    avatar      VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Emails table
CREATE TABLE webmail_emails (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    sender_name     VARCHAR(100) NOT NULL,
    sender_email    VARCHAR(150) NOT NULL,
    receiver_email  TEXT NOT NULL,
    cc              TEXT DEFAULT NULL,
    bcc             TEXT DEFAULT NULL,
    subject         VARCHAR(255) NOT NULL,
    message         LONGTEXT NOT NULL,
    is_html         TINYINT(1) DEFAULT 0,
    priority        ENUM('low','normal','high') DEFAULT 'normal',
    attachments     TEXT DEFAULT NULL,
    status          ENUM('sent','failed','draft','scheduled') DEFAULT 'sent',
    scheduled_at    DATETIME DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES webmail_users(id) ON DELETE CASCADE
);

-- Templates table
CREATE TABLE webmail_email_templates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    name        VARCHAR(100) NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    body        LONGTEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES webmail_users(id) ON DELETE CASCADE
);

-- Password reset tokens
CREATE TABLE webmail_password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default templates
INSERT INTO webmail_email_templates (user_id, name, subject, body) VALUES
(1, 'Internship Certificate', 'Internship Completion Certificate – [Name]',
'Dear [Name],\n\nWe are pleased to certify that you have successfully completed your internship at [Company] from [Start Date] to [End Date].\n\nDuring your tenure, you demonstrated exceptional skills and a professional attitude.\n\nWe wish you the very best in your future endeavors.\n\nWarm regards,\n[HR Name]\n[Company]'),
(1, 'Offer Letter', 'Job Offer – [Position] at [Company]',
'Dear [Candidate Name],\n\nWe are delighted to offer you the position of [Position] at [Company], effective [Joining Date].\n\nYour CTC will be [Salary] per annum. Please confirm your acceptance by [Deadline].\n\nLooking forward to welcoming you to our team.\n\nSincerely,\n[HR Name]'),
(1, 'Business Email', 'Partnership Opportunity – [Your Company]',
'Dear [Name],\n\nI hope this message finds you well. I am reaching out to explore a potential collaboration between our organizations.\n\nWe believe a partnership could be mutually beneficial, and I would love to schedule a call at your earliest convenience.\n\nKind regards,\n[Your Name]\n[Your Company]'),
(1, 'Notification Email', 'Important Update – Action Required',
'Dear [Name],\n\nThis is to inform you that [Event/Action] requires your immediate attention.\n\nPlease [Action Required] by [Deadline] to avoid any disruption.\n\nIf you have any questions, feel free to reach out.\n\nThank you,\n[Team Name]');


ALTER TABLE webmail_users ADD COLUMN signature TEXT DEFAULT NULL;
