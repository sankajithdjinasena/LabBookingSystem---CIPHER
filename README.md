# 🎓 Smart University Resource Allocation System (SURAS)

A smart resource booking and allocation platform designed to automate the management of university resources such as meeting rooms, computer laboratories, multimedia equipment, and testing devices. The system ensures fair, conflict-free, and efficient allocation using priority-based scheduling and intelligent resource management.

---

## 📌 Project Overview

Universities often rely on manual booking processes for shared resources, resulting in scheduling conflicts, unfair allocations, and administrative overhead.

The **Smart University Resource Allocation System (SURAS)** addresses these challenges by automating resource scheduling, conflict detection, priority-based allocation, waitlist management, and real-time availability tracking.

---

## 🚀 Features

- 📅 Real-Time Resource Availability
- ✅ Automated Booking Approval
- ⚠️ Conflict Detection & Resolution
- ⭐ Priority-Based Resource Allocation
- ⏳ Waitlist Management
- 🔄 Alternative Time Slot Recommendations
- 🏢 Alternative Resource Suggestions
- 📊 Resource Usage Analytics
- 📧 Email/SMS Notifications
- 🔔 Booking Expiry & Reminder Notifications
- 👥 Fair Resource Sharing using Round Robin Scheduling

---

## 👨‍💻 User Roles

| Role | Responsibilities |
|------|------------------|
| Student | Submit booking requests |
| Project Team Leader | Manage team resource requests |
| Faculty Member | Validate academic priority requests |
| Administrator | Manage resources, bookings, and system policies |

---

## 🧠 Allocation Strategy

The system combines multiple scheduling algorithms to maximize fairness and efficiency.

### Priority Scheduling

Each booking request is assigned a priority score:

```
Priority Score =
(0.4 × Urgency)
+ (0.3 × Team Size)
+ (0.2 × Fairness Score)
+ (0.1 × Request Time)
```

### Round Robin Scheduling

During periods of high demand, long booking requests are divided into smaller time slots, ensuring equitable access for all users.

---

## 🔄 Resource Allocation Workflow

1. User submits a booking request.
2. System validates the request.
3. Check resource availability.
4. If available → Booking approved.
5. If unavailable:
   - Calculate priority score.
   - Compare conflicting requests.
   - Allocate to highest priority.
   - Suggest alternative slot or resource.
   - Otherwise place in waiting list.
6. Notify users of booking status.

---

## ⚙️ Conflict Resolution

- Detect overlapping bookings.
- Calculate priority scores.
- Allocate resource to highest priority request.
- Recommend nearest available alternative.
- Maintain waiting list.
- Automatically assign cancelled bookings to waiting users.

---

## 🎯 Objectives

- Eliminate scheduling conflicts
- Ensure fair resource allocation
- Improve resource utilization
- Reduce administrative workload
- Support increasing booking demand
- Increase transparency in resource management

---

## 📈 Benefits

- Reduced booking conflicts
- Faster approval process
- Fair and transparent allocation
- Better utilization of university resources
- Improved user experience
- Lower administrative effort

---

## ⚠️ Limitations

- Priority rules may require periodic updates.
- Emergency requests may override existing bookings.
- System accuracy depends on up-to-date resource information.

---

## 🛠️ Suggested Technology Stack

### Frontend
- React.js / Angular / Vue.js / HTML / CSS

### Backend
- Spring Boot / Node.js / Django / PHP

### Database
- MySQL / PostgreSQL

### Authentication
- University SSO / JWT

### Notifications
- Email API
- SMS Gateway

## 👥 Team Predictra

- Harol Maxilan
- Sankajith D. Jinasena
- P. M. Sanodya V. Jinadasa
- Mohomed Yoosuf
- Mathurya Muralimohan

---

## 📄 Case Study

This project was developed as part of the **CIPHER 2.0 Case Analysis Competition**, focusing on designing an intelligent solution for university resource allocation.

---

## 📜 License

This project is developed for academic and educational purposes.