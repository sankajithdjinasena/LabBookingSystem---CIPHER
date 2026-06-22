# SURAS — Smart University Resource Allocation System

A PHP/MySQL web application for booking and managing shared university resources — computer labs, meeting rooms, multimedia equipment and testing devices — with automated conflict resolution, priority-based allocation, round-robin scheduling and email notifications.

---

## Requirements

| Software | Version |
|----------|---------|
| PHP | 8.0 or higher |
| MySQL | 5.7 or higher (MariaDB 10.4+ also works) |
| Apache / Nginx | Any recent version with `mod_rewrite` |
| Composer | Only needed for PHPMailer (optional) |

XAMPP 8.x on Windows covers PHP + MySQL + Apache in one installer and is the recommended local setup.

---

## Installation

### 1 — Copy the project files

Place the `LabBookingSystem` folder (or whatever you named it) into your web root:

- **XAMPP Windows:** `C:\xampp\htdocs\LabBookingSystem\`
- **Linux/macOS:** `/var/www/html/LabBookingSystem/` or your vhost document root

### 2 — Import the database

Open **phpMyAdmin** (http://localhost/phpmyadmin) and:

1. Click **New** in the left sidebar
2. Create a database named `suras` with collation `utf8mb4_unicode_ci`
3. Select the `suras` database, click the **Import** tab
4. Choose `database/suras.sql` and click **Go**

Or from the command line:

```bash
mysql -u root -p -e "CREATE DATABASE suras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p suras < database/suras.sql
```

### 3 — Configure the database connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'suras');
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password (blank for XAMPP default)
```

### 4 — (Optional) Configure email

If you want real email notifications, install PHPMailer:

```bash
cd LabBookingSystem
composer require phpmailer/phpmailer
```

Then edit `includes/config.php`:

```php
define('MAIL_HOST',       'smtp.youruniversity.edu');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'suras@youruniversity.edu');
define('MAIL_PASSWORD',   'your-smtp-password');
define('MAIL_ENCRYPTION', 'tls');
```

Finally, go to **Admin → Settings** and toggle **Enable email notifications** on.

Without PHPMailer or without enabling the setting, all notifications appear in-app only — the system works fully without email.

### 5 — Open in your browser

```
http://localhost/LabBookingSystem/
```

---

## Default accounts

All seed accounts share the password **`Password123!`** — change these immediately in any real deployment.

| Email | Role |
|-------|------|
| harol.admin@university.edu | Administrator |
| a.perera@university.edu | Faculty Member |
| sankajith@university.edu | Project Team Leader |
| mathurya@university.edu | Student |

To change a password: sign in, go to **My Bookings → Account**, or use the **Forgot password** link on the login page (requires email to be configured).

Alternatively, generate a new hash in PHP and update the database:

```php
echo password_hash('NewPassword123!', PASSWORD_DEFAULT);
```

```sql
UPDATE users SET password_hash = '<hash>' WHERE email = 'harol.admin@university.edu';
```

---

## Project structure

```
LabBookingSystem/
│
├── index.php                   Public landing page
├── login.php                   Sign-in
├── register.php                Account creation
├── logout.php                  Session destruction
├── forgot-password.php         Request a reset link
├── reset-password.php          Set a new password via token
├── dashboard.php               User dashboard
├── resources.php               Browse / search / filter resources
├── booking.php                 Create a booking
├── my-bookings.php             Booking history + cancel
├── notifications.php           Notification history
│
├── admin/
│   ├── dashboard.php           Admin overview + activity feed
│   ├── resources.php           Add / edit / delete resources
│   ├── users.php               Manage user accounts and roles
│   ├── bookings.php            Approve / reject booking requests
│   ├── reports.php             Analytics charts and tables
│   └── settings.php            Allocation policy + email config
│
├── faculty/
│   └── approvals.php           Faculty booking review
│
├── includes/
│   ├── config.php              DB credentials, SMTP, app constants
│   ├── database.php            PDO connection (get_db_connection)
│   ├── auth.php                Login/logout/session/role guards
│   ├── functions.php           Core helpers, priority scoring, booking logic
│   ├── admin-functions.php     Admin/faculty queries and actions
│   ├── settings.php            Read/write the settings table
│   ├── mailer.php              PHPMailer email wrapper
│   ├── navbar.php              Public nav (index.php)
│   ├── app-navbar.php          Authenticated user nav
│   └── ops-navbar.php          Admin/faculty nav
│
├── assets/
│   ├── css/style.css           Design system stylesheet
│   └── js/main.js              Mobile nav, password toggle, validation
│
└── database/
    └── suras.sql               Full schema + seed data
```

---

## How the allocation system works

### 1. Priority scoring

Every booking request is assigned a score when submitted:

```
Priority Score =
  (weight_urgency      × Urgency  [1–5 → 0–10])
+ (weight_team_size    × TeamSize [0–10])
+ (weight_fairness     × Fairness [fewer recent bookings = higher score])
+ (weight_request_time × Age      [hours since request, capped at 10])
```

Default weights: 0.4 / 0.3 / 0.2 / 0.1 — adjustable in **Admin → Settings**.

### 2. Conflict resolution

When a new booking is submitted for an already-taken slot:

- If the new request has a **higher priority score** than all existing ones, it is approved and the lower-priority bookings are demoted to the **waitlist**.
- If any existing booking has an equal or higher score, the new request goes to the **waitlist**.
- Waitlisted bookings are offered an alternative slot on the same day (same resource) if one exists.

### 3. Round-robin splitting

When two bookings for a **lab or room** overlap and at least one is longer than the configured threshold (default 4 hours), the overlapping period is split into equal time slots and alternated between the two requesters, starting with the higher-priority one.

Slot duration and the trigger threshold are configurable in **Admin → Settings**.

### 4. Cancellation promotion

When an approved booking is cancelled, SURAS automatically promotes the highest-priority waitlisted booking for that slot and notifies the user.

---

## Upgrading an existing database

If you already have SURAS running and need to add the new tables:

```sql
-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    label VARCHAR(120) NOT NULL,
    description VARCHAR(300) NULL
) ENGINE=InnoDB;

-- Seed default settings
INSERT IGNORE INTO settings (setting_key, setting_value, label, description) VALUES
('weight_urgency',      '0.40', 'Urgency weight', ''),
('weight_team_size',    '0.30', 'Team size weight', ''),
('weight_fairness',     '0.20', 'Fairness weight', ''),
('weight_request_time', '0.10', 'Request time weight', ''),
('rr_min_duration',     '14400','Round-robin threshold (seconds)', ''),
('rr_slot_duration',    '7200', 'Round-robin slot size (seconds)', ''),
('notify_email_enabled','0',    'Email notifications', ''),
('notify_from_email',   'noreply@university.edu', 'Notification sender email', ''),
('notify_from_name',    'SURAS Resource System',  'Notification sender name',  '');

-- Update notifications ENUM to include 'submission'
ALTER TABLE notifications
  MODIFY type ENUM('submission','approval','rejection','cancellation','reminder','waitlist','alternative') NOT NULL;
```

---

## Team Predictra

| Name | Role |
|------|------|
| Harol Maxilan | Team Leader |
| Sankajith D. Jinasena | Member |
| P. M. Sanodya V. Jinadasa | Member |
| Mohomed Yoosuf | Member |
| Mathurya Muralimohan | Member |

Developed for the **CIPHER 2.0 Case Analysis Competition**.
