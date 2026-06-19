
# 🗂 Project Structure

```
SURAS/
│
├── index.php
├── login.php
├── dashboard.php
├── resources.php
├── booking.php
├── my-bookings.php
├── notifications.php
│
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── resources.php
│   ├── bookings.php
│   └── reports.php
│
├── faculty/
│   └── approvals.php
│
├── includes/
│   ├── config.php
│   ├── database.php
│   ├── auth.php
│   └── navbar.php
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── icons/
│
└── database/
    └── suras.sql
```

---

# 🛠 Technology Stack

## Frontend

- HTML5
- CSS3
- JavaScript
- Bootstrap 5

## Backend

- PHP

## Database

- MySQL

## Libraries

- Chart.js
- Font Awesome
- PHPMailer

---

# 🗄 Database Tables

- Users
- Resources
- Bookings
- Waitlist
- Notifications
- Departments

---

# 🔄 System Workflow

```
User Login
     │
     ▼
Dashboard
     │
     ▼
Search Resource
     │
     ▼
Check Availability
     │
 ┌───┴──────────────┐
 │                  │
 ▼                  ▼
Available      Not Available
 │                  │
 ▼                  ▼
Approve      Calculate Priority
Booking            │
 │                  ▼
 ▼          Alternative Resource
Notification        │
                    ▼
             Waiting List
```

---
