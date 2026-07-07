-- Almighty Driving School Database Setup
-- Designed by Group 9
-- Refactored to Singular Entity Naming Convention (Unquoted Standard SQL Format)

DROP DATABASE IF EXISTS almighty_driving_school;
CREATE DATABASE IF NOT EXISTS almighty_driving_school CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE almighty_driving_school;

-- 1. MANAGER
CREATE TABLE IF NOT EXISTS manager (
    manager_id INT AUTO_INCREMENT PRIMARY KEY,
    manager_name VARCHAR(100) NOT NULL,
    manager_role VARCHAR(50) NOT NULL DEFAULT 'Manager',
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 2. STUDENT
CREATE TABLE IF NOT EXISTS student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    surname VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    date_of_birth DATE NOT NULL,
    place_of_birth VARCHAR(100) DEFAULT NULL,
    residential_address VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    national_id VARCHAR(50) UNIQUE NOT NULL,
    occupation VARCHAR(100) DEFAULT NULL,
    permit_number VARCHAR(50) DEFAULT NULL,
    permit_expiry_date DATE DEFAULT NULL
) ENGINE=InnoDB;

-- 3. REGISTRATION
CREATE TABLE IF NOT EXISTS registration (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    registration_date DATE NOT NULL,
    commencement_date DATE DEFAULT NULL,
    completion_date DATE DEFAULT NULL,
    course_name VARCHAR(100) NOT NULL DEFAULT 'Beginner Driving Course',
    total_cost DECIMAL(10,2) NOT NULL DEFAULT 1200.00,
    deposit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL DEFAULT 1200.00,
    student_id INT NOT NULL,
    manager_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES student (student_id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES manager (manager_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. PAYMENT
CREATE TABLE IF NOT EXISTS payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type VARCHAR(50) NOT NULL DEFAULT 'Cash', -- Cash, Bank Transfer, Mobile Money
    balance_after_payment DECIMAL(10,2) NOT NULL,
    registration_id INT NOT NULL,
    FOREIGN KEY (registration_id) REFERENCES registration (registration_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. INSTRUCTOR
CREATE TABLE IF NOT EXISTS instructor (
    instructor_id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_name VARCHAR(100) NOT NULL,
    instructor_dob DATE DEFAULT NULL,
    national_id VARCHAR(50) UNIQUE NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    license_type VARCHAR(20) NOT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB;

-- 6. VEHICLE
CREATE TABLE IF NOT EXISTS vehicle (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_registration_no VARCHAR(50) UNIQUE NOT NULL,
    vehicle_identity_no VARCHAR(50) UNIQUE NOT NULL, -- VIN
    vehicle_type VARCHAR(50) NOT NULL, -- Manual Sedan, Automatic SUV, Light Truck, etc.
    status VARCHAR(20) NOT NULL DEFAULT 'Active' -- Active, Maintenance, Out of Service
) ENGINE=InnoDB;

-- 7. LESSON
CREATE TABLE IF NOT EXISTS lesson (
    lesson_id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_type VARCHAR(50) NOT NULL, -- Practical, Theory, Assessment
    lesson_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Scheduled', -- Scheduled, Completed, Cancelled
    registration_id INT NOT NULL,
    instructor_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    FOREIGN KEY (registration_id) REFERENCES registration (registration_id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructor (instructor_id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicle (vehicle_id) ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ==========================================
-- SEED DATA
-- ==========================================

-- Seed manager
INSERT INTO manager (manager_id, manager_name, manager_role, username, password) VALUES
(1, 'Hafsah Salifu', 'General Manager', 'hafsah', '$2y$10$t6HGgASKRS2C/lYPYk1ZNOhaYHi2id2y9bX0IqwmT9MnioT6Exu2y'),
(2, 'Moses Sedodey', 'Operations Manager', 'moses', '$2y$10$t6HGgASKRS2C/lYPYk1ZNOhaYHi2id2y9bX0IqwmT9MnioT6Exu2y');

-- Seed student
INSERT INTO student (student_id, surname, first_name, middle_name, date_of_birth, place_of_birth, residential_address, telephone, email, national_id, occupation, permit_number, permit_expiry_date) VALUES
(1, 'Agyapong', 'Joseph', 'Apostle', '1998-05-15', 'Kumasi', 'Tanoso, Kumasi', '0244123456', 'joseph.agyapong@gmail.com', 'GHA-720192842-1', 'Student', 'DS-8273918-B', '2027-12-31'),
(2, 'Wornyo', 'Divine', 'Paul Kwabla', '1995-10-22', 'Ho', 'Sofoline, Kumasi', '0555987654', 'divine.wornyo@yahoo.com', 'GHA-819283741-2', 'Teacher', 'DS-9283172-C', '2028-06-30'),
(3, 'Amoah', 'Arthur', 'Peter', '2001-02-10', 'Accra', 'Kotei, Kumasi', '0207112233', 'arthur.peter@outlook.com', 'GHA-938271635-0', 'Engineer', NULL, NULL), -- No permit yet
(4, 'Boateng', 'Eric', NULL, '1999-09-08', 'Sunyani', 'Bantama, Kumasi', '0243445566', 'eric.boateng@gmail.com', 'GHA-102938475-6', 'Salesperson', 'DS-1029384-A', '2026-08-15'),
(5, 'Asomani', 'Obed', NULL, '2003-12-05', 'Kumasi', 'Kwadaso, Kumasi', '0502233445', 'obed.asomani@edu.gh', 'GHA-928371234-9', 'Apprentice', 'DS-2938471-B', '2025-12-01'); -- Expired permit

-- Seed registration
INSERT INTO registration (registration_id, registration_date, commencement_date, completion_date, course_name, total_cost, deposit, balance, student_id, manager_id) VALUES
(1, '2026-06-01', '2026-06-05', '2026-07-05', 'Beginner Driving Course', 1200.00, 600.00, 600.00, 1, 1),
(2, '2026-06-10', '2026-06-12', NULL, 'Refresher Driving Course', 700.00, 700.00, 0.00, 2, 2), -- Fully Paid
(3, '2026-06-15', '2026-06-20', NULL, 'Defensive Driving Course', 1500.00, 500.00, 1000.00, 3, 1),
(4, '2026-06-20', NULL, NULL, 'Beginner Driving Course', 1200.00, 0.00, 1200.00, 4, 2); -- Not commenced, no deposit

-- Seed payment
INSERT INTO payment (payment_id, payment_date, amount, payment_type, balance_after_payment, registration_id) VALUES
(1, '2026-06-01', 600.00, 'Cash', 600.00, 1),
(2, '2026-06-10', 700.00, 'Mobile Money', 0.00, 2),
(3, '2026-06-15', 500.00, 'Bank Transfer', 1000.00, 3),
(4, '2026-06-25', 300.00, 'Mobile Money', 300.00, 1); -- Second payment for registration 1, balance becomes 300
-- Update registration 1's balance to reflect the second payment in registration table
UPDATE registration SET balance = 300.00 WHERE registration_id = 1;

-- Seed instructor
INSERT INTO instructor (instructor_id, instructor_name, instructor_dob, national_id, license_number, license_type, telephone, email) VALUES
(1, 'Kofi Mensah', '1980-04-12', 'GHA-123456789-0', 'LIC-9283719-F', 'Class F', '0244778899', 'kofi.mensah@almightydriving.com'),
(2, 'Emmanuel Owusu', '1987-11-23', 'GHA-987654321-1', 'LIC-1029384-E', 'Class E', '0543112233', 'emmanuel.owusu@almightydriving.com'),
(3, 'Isaac Yakubu Ngor', '1992-07-02', 'GHA-456123789-2', 'LIC-2938471-D', 'Class D', '0209554433', 'isaac.ngor@almightydriving.com');

-- Seed vehicle
INSERT INTO vehicle (vehicle_id, vehicle_registration_no, vehicle_identity_no, vehicle_type, status) VALUES
(1, 'AS-1024-24', 'VIN-827391823791-MA', 'Manual Toyota Yaris', 'Active'),
(2, 'GW-2098-25', 'VIN-928371928371-AU', 'Automatic Honda Civic', 'Active'),
(3, 'AS-9988-23', 'VIN-102938475610-TR', 'Light Manual Truck', 'Maintenance'),
(4, 'GW-4402-26', 'VIN-129384710293-BS', 'Driving School Bus', 'Active');

-- Seed lesson
INSERT INTO lesson (lesson_id, lesson_type, lesson_date, start_time, end_time, status, registration_id, instructor_id, vehicle_id) VALUES
(1, 'Practical', '2026-06-08', '08:00:00', '10:00:00', 'Completed', 1, 1, 1),
(2, 'Theory', '2026-06-10', '14:00:00', '16:00:00', 'Completed', 1, 2, 4),
(3, 'Practical', '2026-06-15', '10:00:00', '12:00:00', 'Completed', 2, 3, 2),
-- Future Scheduled lesson
(4, 'Practical', '2026-07-02', '09:00:00', '11:00:00', 'Scheduled', 1, 1, 1),
(5, 'Practical', '2026-07-02', '13:00:00', '15:00:00', 'Scheduled', 3, 1, 2);
