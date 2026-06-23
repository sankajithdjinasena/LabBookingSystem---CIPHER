# NEXLAB — Smart University Resource Allocation System

> A PHP/MySQL web application for booking and managing shared university resources — computer labs, meeting rooms, multimedia equipment, and testing devices — with an AI-powered Intelligence Dashboard, automated conflict resolution, priority-based allocation, round-robin scheduling, and real-time behavioral tracking.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [System Architecture](#system-architecture)
- [Requirements](#requirements)
- [Installation & Setup](#installation--setup)
- [Default Accounts](#default-accounts)
- [How Allocation Works](#how-allocation-works)
- [Intelligence Dashboard](#intelligence-dashboard)
- [Transition & Maintenance Policy](#transition--maintenance-policy)
- [Project Structure](#project-structure)
- [Team](#team)

---

## Overview

NEXLAB is a full-stack university lab resource management system developed for the **CIPHER 2.0 Case Analysis Competition**. It addresses the core institutional problem of uncoordinated, manual resource booking that leads to double-bookings, underutilised labs, and inequitable access.

NEXLAB replaces ad-hoc spreadsheets and email chains with an automated, priority-driven booking engine — supported by an Intelligence Dashboard that provides predictive demand forecasting and behavioral activity tracking for administrators.

---

## Key Features

| Feature | Description |
|---|---|
| **Role-based Access** | Four roles: Administrator, Faculty, Project Team Leader, Student |
| **Priority Scoring Engine** | Automatic scoring of every booking request based on urgency, team size, fairness, and request time |
| **Conflict Resolution** | Automatically promotes higher-priority requests and demotes lower-priority ones to a waitlist |
| **Round-Robin Splitting** | Long overlapping bookings for labs/rooms are divided into alternating fair slots |
| **Waitlist Auto-Promotion** | When a booking is cancelled, the next highest-priority waitlisted request is automatically approved |
| **Intelligence Dashboard** | Predictive 7-day demand forecasting and behavioral activity flags |
| **Behavioral Activity Tracking** | Dynamic threshold filters to identify high-volume bookers and batch booking sessions |
| **Support Chat** | In-app messaging between students and administrators |
| **Email Notifications** | Optional PHPMailer-powered SMTP notifications for all booking events |
| **Transition Period Policy** | Mandatory +/- 10 minute maintenance buffer enforced via UI notices |
| **Admin Analytics** | Resource utilisation reports, booking trends, and flag management |

---

## System Architecture

```
+-------------------------------------------------------------+
|                         NEXLAB                              |
|                                                             |
|  +--------------+  +--------------+  +------------------+  |
|  |  Public UI   |  |  User Area   |  |  Admin/Faculty   |  |
|  |  index.php   |  | dashboard.php|  |  admin/          |  |
|  |  login.php   |  | booking.php  |  |  faculty/        |  |
|  |  register.php|  | resources.php|  |  Vertical Sidebar|  |
|  +------+-------+  +------+-------+  +--------+---------+  |
|         |                 |                    |            |
|  +------v-----------------v--------------------v---------+  |
|  |                  includes/ (Core Logic)               |  |
|  |  functions.php | analytics.php | auth.php             |  |
|  |  admin-functions | settings.php | mailer.php          |  |
|  +----------------------------+---------------------------+  |
|                               | PDO                         |
|  +----------------------------v---------------------------+  |
|  |                     MySQL Database                    |  |
|  |  users | bookings | resources | notifications         |  |
|  |  settings | support_tickets | messages                |  |
|  +-------------------------------------------------------+  |
+-------------------------------------------------------------+
```

**Tech Stack:**
- **Backend:** PHP 7.4+ with PDO (prepared statements throughout)
- **Database:** MySQL 5.7+ / MariaDB 10.4+
- **Frontend:** Vanilla HTML5, CSS3, JavaScript (no frameworks)
- **Email:** PHPMailer (optional)
- **Server:** Apache (WAMP/XAMPP recommended for local development)

---

## Requirements

| Software | Version |
|---|---|
| PHP | 7.4 or higher |
| MySQL | 5.7 or higher (MariaDB 10.4+ also works) |
| Apache | Any recent version with `mod_rewrite` |
| Composer | Only needed for PHPMailer (optional) |

WAMP 3.x or XAMPP 8.x on Windows covers PHP + MySQL + Apache in one installer and is the recommended local setup.

---

## Installation & Setup

### 1 — Clone the repository

```bash
git clone https://github.com/your-repo/Lab-Booking-System.git
```

Place the folder into your web root:

- **WAMP Windows:** `C:\wamp64\www\Lab-Booking-System\`
- **XAMPP Windows:** `C:\xampp\htdocs\Lab-Booking-System\`
- **Linux/macOS:** `/var/www/html/Lab-Booking-System/`

### 2 — Import the database

Open **phpMyAdmin** (http://localhost/phpmyadmin) and:

1. Click **New** → create a database named `NEXLAB` with collation `utf8mb4_unicode_ci`
2. Select the `NEXLAB` database → click **Import**
3. Choose `nexlab.sql` from the project root → click **Go**

Or from the command line:

```bash
mysql -u root -p -e "CREATE DATABASE NEXLAB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p NEXLAB < nexlab.sql
```

### 3 — Configure the database connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'NEXLAB');
define('DB_USER', 'root');      // your MySQL username
define('DB_PASS', '');          // your MySQL password (blank for WAMP/XAMPP default)
```

### 4 — (Optional) Configure email notifications

```bash
cd Lab-Booking-System
composer require phpmailer/phpmailer
```

Then edit `includes/config.php`:

```php
define('MAIL_HOST',       'smtp.youruniversity.edu');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'nexlab@youruniversity.edu');
define('MAIL_PASSWORD',   'your-smtp-password');
define('MAIL_ENCRYPTION', 'tls');
```

Go to **Admin → Settings** and toggle **Enable email notifications** on.

### 5 — Open in your browser

```
http://localhost/Lab-Booking-System/
```

---

## Default Accounts

All seed accounts share the password **`Password123!`** — change these immediately in any real deployment.

| Email | Role |
|---|---|
| `harol.admin@university.edu` | Administrator |
| `a.perera@university.edu` | Faculty Member |
| `sankajith@university.edu` | Project Team Leader |
| `mathurya@university.edu` | Student |

To reset a password:

```php
echo password_hash('NewPassword123!', PASSWORD_DEFAULT);
```

```sql
UPDATE users SET password_hash = '<hash>' WHERE email = 'harol.admin@university.edu';
```

---

## How Allocation Works

### 1. Priority Scoring

Every booking request is automatically scored on submission:

```
Priority Score =
  (weight_urgency      x Urgency  [1-5 -> 0-10])
+ (weight_team_size    x TeamSize [0-10])
+ (weight_fairness     x Fairness [fewer recent bookings = higher score])
+ (weight_request_time x Age      [hours since request, capped at 10])
```

Default weights: **0.4 / 0.3 / 0.2 / 0.1** — adjustable in **Admin → Settings**.

### 2. Conflict Resolution

When a new booking conflicts with an existing one:

- **Higher priority score** → new request is approved; lower-priority booking is demoted to waitlist.
- **Equal or lower priority** → new request goes to the waitlist with an automatic alternative slot suggestion.

### 3. Round-Robin Splitting

When two bookings for a lab or room overlap and at least one is ≥4 hours, the overlapping period is split into equal slots and alternated between requesters by priority. Duration and threshold are configurable in **Admin → Settings**.

### 4. Cancellation Promotion

When an approved booking is cancelled, the highest-priority waitlisted booking for that slot is automatically approved and the user is notified.

### 5. Approval Flow

- **Administrators:** Bookings are auto-approved.
- **All other roles:** Bookings are submitted as `pending` and require Admin review.

---

## Intelligence Dashboard

Access via **Admin → Intelligence**.

### Demand Forecasting
- Analyses an 8-week rolling booking history per resource.
- Predicts utilisation percentage for each of the next 7 days.
- Flags days predicted to exceed **80% utilisation** as high-risk.

### Behavioral Activity & Flags

Four tracking signals with **Live Threshold Meters** and **real-time filter controls**:

| Signal | Default Threshold | Severity |
|---|---|---|
| High Volume Booker | 40 bookings / 7 days | Medium |
| Urgency Score Anomaly | 70% max-urgency rate | High/Critical |
| High Resource Dependency | 3 bookings same resource / 7 days | Medium |
| Batch Booking Session | 40 bookings / 10 min window | Low |

Admins can adjust thresholds dynamically from the dashboard — essential during lecture weeks when class representatives legitimately submit large volumes of bookings.

---

## Transition & Maintenance Policy

All resources include a **mandatory +/- 10 minute transition period** at the start of each booking for room clearing, cleaning, and equipment reset.

This is enforced as a **UI policy** — not a database constraint — to preserve clean hourly scheduling. Users are notified:
1. On the **booking form** via a prominent yellow banner.
2. On the **dashboard confirmation** message after a successful submission.

---

## Project Structure

```
Lab-Booking-System/
├── index.php                   Public landing page
├── login.php                   Sign-in
├── register.php                Account creation
├── logout.php                  Session destruction
├── forgot-password.php         Password reset request
├── reset-password.php          Set new password via token
├── dashboard.php               User dashboard (role-aware)
├── resources.php               Browse / search / filter resources
├── booking.php                 Create a booking
├── my-bookings.php             Booking history + cancel
├── notifications.php           Notification history
├── support.php                 Student support chat
├── nexlab.sql                  Full schema + seed data
│
├── admin/
│   ├── dashboard.php           Admin overview + activity feed
│   ├── resources.php           Add / edit / delete resources
│   ├── users.php               Manage user accounts and roles
│   ├── bookings.php            Approve / reject booking requests
│   ├── reports.php             Analytics and booking trends
│   ├── support.php             Admin support ticket management
│   ├── analytics.php           Intelligence Dashboard
│   └── settings.php            Allocation policy + email config
│
├── faculty/
│   └── approvals.php           Faculty booking review panel
│
├── includes/
│   ├── config.php              DB credentials, SMTP, app constants
│   ├── database.php            PDO connection factory
│   ├── auth.php                Login, logout, session, role guards
│   ├── functions.php           Priority scoring & booking pipeline
│   ├── admin-functions.php     Admin/faculty queries and actions
│   ├── analytics.php           Intelligence engine (forecast + flags)
│   ├── settings.php            Read/write the settings table
│   ├── mailer.php              PHPMailer email wrapper
│   ├── navbar.php              Public navigation
│   ├── app-navbar.php          Authenticated user navigation
│   └── ops-navbar.php          Admin/faculty vertical sidebar nav
│
└── assets/
    ├── css/style.css           Full design system stylesheet
    ├── js/main.js              Client-side interaction layer
    └── img/                    Static images and logo
```

---

## Team

**Team Predictra** — CIPHER 2.0 Case Analysis Competition

| Name | Role |
|---|---|
| Harol Maxilan | Team Leader |
| Sankajith D. Jinasena | Member |
| P. M. Sanodya V. Jinadasa | Member |
| Mohomed Yoosuf | Member |
| Mathurya Muralimohan | Member |

---

*NEXLAB — Built for efficient, intelligent university resource management.*
