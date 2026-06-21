-- =========================================================
-- SURAS — database/suras.sql
-- Full schema for the booking flow: users, departments,
-- resources, bookings, waitlist, notifications.
-- =========================================================

CREATE DATABASE IF NOT EXISTS suras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE suras;

-- ---------------------------------------------------------
-- Departments
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS departments (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO departments (name) VALUES
('Computer Science'), ('Engineering'), ('Business School'), ('Resource Office');

-- ---------------------------------------------------------
-- Users
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120)        NOT NULL,
    email         VARCHAR(190)        NOT NULL UNIQUE,
    password_hash VARCHAR(255)        NOT NULL,
    role          ENUM('student','faculty','project_lead','admin') NOT NULL DEFAULT 'student',
    department    VARCHAR(120)        NULL,
    status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Resources
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS resources (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    category    ENUM('lab','room','multimedia','device') NOT NULL,
    location    VARCHAR(150) NULL,
    capacity    INT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    status      ENUM('available','maintenance','retired') NOT NULL DEFAULT 'available',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO resources (name, category, location, capacity, description, status) VALUES
('Computer Lab 204',       'lab',        'Tech Building, Floor 2', 40, 'Windows lab with 40 workstations and dual monitors.', 'available'),
('Computer Lab 118',       'lab',        'Tech Building, Floor 1', 30, 'Linux lab used mainly for systems and networking courses.', 'available'),
('Seminar Room B',         'room',       'Main Hall, Floor 1',     18, 'Round-table seminar room with a whiteboard wall.', 'available'),
('Conference Room A',      'room',       'Admin Block, Floor 3',   12, 'Glass-walled meeting room with video conferencing.', 'available'),
('Lecture Hall LH-3',      'room',       'Academic Block, Ground', 120, 'Tiered lecture hall with PA system.', 'available'),
('Projector Kit 02',       'multimedia', 'AV Store',               NULL, 'Portable HD projector with tripod screen.', 'available'),
('Mobile PA System',       'multimedia', 'AV Store',               NULL, 'Speaker, mixer and two wireless mics.', 'available'),
('DSLR Camera Kit',        'multimedia', 'AV Store',               NULL, 'Camera, tripod and lavalier mic for recordings.', 'maintenance'),
('VR Headset Set (x4)',    'device',     'Innovation Lab',         4, 'Standalone VR headsets for prototyping sessions.', 'available'),
('Oscilloscope Bench Kit', 'device',     'Engineering Lab 2',      NULL, 'Bench oscilloscope and probes for lab assignments.', 'available');

-- ---------------------------------------------------------
-- Bookings
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    resource_id     INT UNSIGNED NOT NULL,
    purpose         VARCHAR(255) NOT NULL,
    start_time      DATETIME NOT NULL,
    end_time        DATETIME NOT NULL,
    urgency         TINYINT UNSIGNED NOT NULL DEFAULT 1,   -- 1 (low) – 5 (high)
    team_size       INT UNSIGNED NOT NULL DEFAULT 1,
    priority_score  DECIMAL(5,2) NULL,
    status          ENUM('pending','approved','rejected','waitlist','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user     FOREIGN KEY (user_id)     REFERENCES users (id)     ON DELETE CASCADE,
    CONSTRAINT fk_bookings_resource FOREIGN KEY (resource_id) REFERENCES resources (id) ON DELETE CASCADE,
    INDEX idx_bookings_resource_time (resource_id, start_time, end_time)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Waitlist
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS waitlist (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL,
    resource_id INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    start_time  DATETIME NOT NULL,
    end_time    DATETIME NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_waitlist_booking  FOREIGN KEY (booking_id)  REFERENCES bookings (id)  ON DELETE CASCADE,
    CONSTRAINT fk_waitlist_resource FOREIGN KEY (resource_id) REFERENCES resources (id) ON DELETE CASCADE,
    CONSTRAINT fk_waitlist_user     FOREIGN KEY (user_id)     REFERENCES users (id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Notifications
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    type       ENUM('approval','rejection','cancellation','reminder','waitlist','alternative') NOT NULL,
    message    VARCHAR(500) NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
    CONSTRAINT fk_notifications_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sample accounts — all share the password: Password123!
-- (bcrypt hash below was generated for that exact string;
-- PHP's password_verify() accepts $2b$ the same as $2y$.)
-- Change these passwords after first login in any real deployment.
-- ---------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role, department) VALUES
('Harol Maxilan',        'harol.admin@university.edu',  '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'admin',        'Resource Office'),
('Dr. A. Perera',        'a.perera@university.edu',     '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'faculty',      'Computer Science'),
('Sankajith Jinasena',   'sankajith@university.edu',    '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'project_lead', 'Computer Science'),
('Mathurya Muralimohan', 'mathurya@university.edu',     '$2b$12$83m9pMyfi7ubl7ZiBlrZL.umhpn3aWl/hbOFfKP.LFa7iUXy9VJtW', 'student',      'Computer Science');