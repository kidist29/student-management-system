-- =========================================================
-- Student Management System - Database Schema
-- =========================================================
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < database.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS student_management_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE student_management_system;

-- ---------------------------------------------------------
-- Table: users  (login accounts — all roles)
-- Starts as a plain admin-only table for backward compatibility; the
-- "Role-based access control upgrade" block further below adds the
-- role options and teacher_id/student_id link columns. This keeps a
-- fresh import and an upgrade of an existing database on the exact
-- same code path — see that block for details.
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: departments
-- ---------------------------------------------------------
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(20) NOT NULL UNIQUE,
    department_name VARCHAR(100) NOT NULL,
    department_head VARCHAR(100) NULL,
    description TEXT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: teachers
-- ---------------------------------------------------------
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    department_id INT NULL,
    qualification VARCHAR(150) NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    photo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teachers_department FOREIGN KEY (department_id)
        REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_teachers_name (last_name, first_name),
    INDEX idx_teachers_department (department_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: courses
-- ---------------------------------------------------------
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL,
    credit_hours DECIMAL(3,1) NOT NULL DEFAULT 3.0,
    department_id INT NULL,
    semester ENUM('1st Semester', '2nd Semester', 'Summer') NOT NULL DEFAULT '1st Semester',
    teacher_id INT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_courses_department FOREIGN KEY (department_id)
        REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id)
        REFERENCES teachers(id) ON DELETE SET NULL,
    INDEX idx_courses_department (department_id),
    INDEX idx_courses_teacher (teacher_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: students
-- ---------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    date_of_birth DATE NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    department_id INT NULL,
    program VARCHAR(100) NULL,
    year_level TINYINT NOT NULL DEFAULT 1,
    semester ENUM('1st Semester', '2nd Semester', 'Summer') NOT NULL DEFAULT '1st Semester',
    admission_date DATE NULL,
    photo VARCHAR(255) NULL,
    status ENUM('Active', 'Inactive', 'Graduated') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_department FOREIGN KEY (department_id)
        REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_students_name (last_name, first_name),
    INDEX idx_students_department (department_id),
    INDEX idx_students_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- Sample data
-- =========================================================

-- Demo admin account -> email: admin@sms.edu  password: Admin@123
INSERT INTO users (full_name, email, password, role, status) VALUES
('System Administrator', 'admin@sms.edu', '$2b$12$icrNvMKwHZ7Y7O9outCxgOPQUXixvYRwi27fV/.cOhSnjnnB8wfte', 'admin', 'Active');

INSERT INTO departments (department_code, department_name, department_head, description, status) VALUES
('CS',  'Computer Science',        'Dr. Alemayehu Tesfaye', 'Software engineering, systems, and data science.', 'Active'),
('EE',  'Electrical Engineering',  'Dr. Sara Mekonnen',     'Power systems, electronics, and communications.', 'Active'),
('BUS', 'Business Administration', 'Dr. Michael Girma',     'Management, marketing, and finance.', 'Active'),
('ARC', 'Architecture',            'Dr. Hana Bekele',       'Architectural design and urban planning.', 'Active');

INSERT INTO teachers (teacher_code, first_name, last_name, gender, email, phone, department_id, qualification, status) VALUES
('T-1001', 'Alemayehu', 'Tesfaye', 'Male',   'alemayehu.tesfaye@sms.edu', '+251911000111', 1, 'PhD in Computer Science', 'Active'),
('T-1002', 'Sara',      'Mekonnen','Female', 'sara.mekonnen@sms.edu',     '+251911000112', 2, 'PhD in Electrical Engineering', 'Active'),
('T-1003', 'Michael',   'Girma',   'Male',   'michael.girma@sms.edu',    '+251911000113', 3, 'MBA, PhD in Management', 'Active'),
('T-1004', 'Hana',      'Bekele',  'Female', 'hana.bekele@sms.edu',      '+251911000114', 4, 'MSc in Architecture', 'Active'),
('T-1005', 'Dawit',     'Alemu',   'Male',   'dawit.alemu@sms.edu',      '+251911000115', 1, 'MSc in Software Engineering', 'Active');

INSERT INTO courses (course_code, course_name, credit_hours, department_id, semester, teacher_id, status) VALUES
('CS101', 'Introduction to Programming',   4.0, 1, '1st Semester', 1, 'Active'),
('CS205', 'Data Structures & Algorithms',  4.0, 1, '2nd Semester', 5, 'Active'),
('EE110', 'Circuit Analysis',              3.5, 2, '1st Semester', 2, 'Active'),
('BUS150','Principles of Management',      3.0, 3, '1st Semester', 3, 'Active'),
('ARC120','Architectural Design Studio I', 5.0, 4, '2nd Semester', 4, 'Active');

INSERT INTO students (student_id, first_name, last_name, gender, date_of_birth, email, phone, address, department_id, program, year_level, semester, admission_date, status) VALUES
('STU-2024-001', 'Kidist',    'Bekele',   'Female', '2003-04-12', 'kidist.bekele@student.sms.edu',   '+251922000001', 'Bole, Addis Ababa',      1, 'BSc Computer Science',      3, '1st Semester', '2022-09-05', 'Active'),
('STU-2024-002', 'Yonas',     'Tadesse',  'Male',   '2002-11-02', 'yonas.tadesse@student.sms.edu',   '+251922000002', 'Kirkos, Addis Ababa',    2, 'BSc Electrical Engineering',4, '2nd Semester', '2021-09-06', 'Active'),
('STU-2024-003', 'Selam',     'Hailu',    'Female', '2004-01-20', 'selam.hailu@student.sms.edu',     '+251922000003', 'Gerji, Addis Ababa',     3, 'BA Business Administration',2, '1st Semester', '2023-09-04', 'Active'),
('STU-2024-004', 'Nahom',     'Girma',    'Male',   '2003-07-15', 'nahom.girma@student.sms.edu',     '+251922000004', 'Lideta, Addis Ababa',    4, 'BSc Architecture',          1, '1st Semester', '2024-09-02', 'Active'),
('STU-2024-005', 'Mekdes',    'Alemu',    'Female', '2001-09-09', 'mekdes.alemu@student.sms.edu',    '+251922000005', 'Piazza, Addis Ababa',    1, 'BSc Computer Science',      4, '2nd Semester', '2020-09-07', 'Graduated'),
('STU-2024-006', 'Biniam',    'Wolde',    'Male',   '2003-02-28', 'biniam.wolde@student.sms.edu',    '+251922000006', 'Megenagna, Addis Ababa', 2, 'BSc Electrical Engineering',2, '1st Semester', '2023-09-04', 'Active'),
('STU-2024-007', 'Ruth',      'Solomon',  'Female', '2004-05-18', 'ruth.solomon@student.sms.edu',    '+251922000007', 'CMC, Addis Ababa',       3, 'BA Business Administration',1, '2nd Semester', '2024-09-02', 'Inactive'),
('STU-2024-008', 'Abel',      'Mulugeta', 'Male',   '2002-12-01', 'abel.mulugeta@student.sms.edu',   '+251922000008', 'Summit, Addis Ababa',    1, 'BSc Computer Science',      3, '1st Semester', '2022-09-05', 'Active');

-- =========================================================
-- Enrollment & Academic Records extension
-- Added in a later update — existing tables above are untouched.
-- Re-importing this whole file is safe on a fresh database; on an
-- EXISTING database that already has students/teachers/departments/
-- courses/users, run just the statements from this line down.
-- =========================================================

-- ---------------------------------------------------------
-- Table: enrollments  (a student registered for a course in a given term)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester ENUM('1st Semester', '2nd Semester', 'Summer') NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('Enrolled', 'Completed', 'Dropped') NOT NULL DEFAULT 'Enrolled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_enrollment (student_id, course_id, academic_year, semester),
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_enrollments_student (student_id),
    INDEX idx_enrollments_course (course_id),
    INDEX idx_enrollments_term (academic_year, semester)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: grades  (one grade per enrollment)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL UNIQUE,
    grade_letter ENUM('A','A-','B+','B','B-','C+','C','C-','D+','D','F') NOT NULL,
    grade_point DECIMAL(3,2) NOT NULL,
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grades_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: contact_messages  (submissions from the public Contact page)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: settings  (key/value store, editable by Super Admin)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('institution_name', 'Riverside College'),
('institution_address', 'Riverside Campus, Bole Road, Addis Ababa, Ethiopia'),
('institution_email', 'registrar@riverside.edu'),
('institution_phone', '+251 11 234 5678'),
('institution_hours', 'Monday – Friday, 8:30 AM – 5:00 PM')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ---------------------------------------------------------
-- Role-based access control upgrade for `users`
--
-- This is the single path that adds role-based access control, whether
-- you're importing this file fresh or upgrading a database that already
-- has the original admin-only `users` table — both start from the same
-- plain CREATE TABLE above, so these statements always apply cleanly.
-- ---------------------------------------------------------
ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'admin';
ALTER TABLE users ADD COLUMN teacher_id INT NULL AFTER role;
ALTER TABLE users ADD COLUMN student_id INT NULL AFTER teacher_id;
ALTER TABLE users
    ADD CONSTRAINT fk_users_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    ADD UNIQUE KEY uniq_user_teacher (teacher_id),
    ADD UNIQUE KEY uniq_user_student (student_id);

-- Promote the original demo admin to Super Admin (keeps the same login
-- working exactly as before — see section 4, "Demo login information").
UPDATE users SET role = 'super_admin' WHERE email = 'admin@sms.edu';

-- Demo accounts for the other three roles. Passwords below (bcrypt-hashed,
-- never stored in plain text): Registrar@123 · Teacher@123 · Student@123
INSERT INTO users (full_name, email, password, role, teacher_id, student_id, status) VALUES
('Hana Desta', 'registrar@sms.edu', '$2b$12$a1V4tn58DCP5ShVJy6XMYu9dbUs1zoMT.es2cBCRNspv/p5IuZUC6', 'admin', NULL, NULL, 'Active'),
('Alemayehu Tesfaye', 'alemayehu.tesfaye@sms.edu', '$2b$12$1.Vkf0cmAhsoX4jIo0VBmuwxFKdRz.NPFVJ8tUzWxKQV9mDNwrNIq', 'teacher', 1, NULL, 'Active'),
('Kidist Bekele', 'kidist.bekele@student.sms.edu', '$2b$12$/z9nG5kzXs9r5uQbmz34dezk9IfusqACzRdLI4CIARZe0V17rYntO', 'student', NULL, 1, 'Active')
ON DUPLICATE KEY UPDATE email = email;

-- ---------------------------------------------------------
-- Automatic grading: add a numeric score, which the application computes
-- grade_letter and grade_point FROM (see scoreToGrade() in
-- includes/functions.php) — the scale is a single array there, easy to
-- retune without touching the database.
-- ---------------------------------------------------------
ALTER TABLE grades ADD COLUMN score DECIMAL(5,2) NULL AFTER enrollment_id;

-- ---------------------------------------------------------
-- Performance: indexes for columns used in exact-match filters across the
-- Courses and Enrollments list pages (search-by-text columns like name/email
-- don't benefit from a plain index with a LIKE '%term%' pattern, so those
-- are intentionally left as-is).
-- ---------------------------------------------------------
ALTER TABLE courses ADD INDEX idx_courses_status (status);
ALTER TABLE courses ADD INDEX idx_courses_semester (semester);
ALTER TABLE enrollments ADD INDEX idx_enrollments_status (status);

-- ---------------------------------------------------------
-- Sample enrollments + grades
-- (student 1 = Kidist Bekele, student 5 = Mekdes Alemu/Graduated, etc.
--  ids below match the sample students/courses inserted earlier in this file)
-- ---------------------------------------------------------
INSERT INTO enrollments (student_id, course_id, academic_year, semester, enrollment_date, status) VALUES
(1, 1, '2024/2025', '1st Semester', '2024-09-10', 'Completed'),
(1, 2, '2025/2026', '2nd Semester', '2025-02-05', 'Enrolled'),
(2, 3, '2024/2025', '1st Semester', '2024-09-12', 'Completed'),
(3, 4, '2024/2025', '1st Semester', '2024-09-10', 'Completed'),
(4, 5, '2025/2026', '2nd Semester', '2025-02-05', 'Enrolled'),
(5, 1, '2021/2022', '1st Semester', '2021-09-15', 'Completed'),
(5, 2, '2022/2023', '2nd Semester', '2023-02-10', 'Completed'),
(6, 3, '2024/2025', '1st Semester', '2024-09-12', 'Completed'),
(8, 1, '2024/2025', '1st Semester', '2024-09-10', 'Completed');

INSERT INTO grades (enrollment_id, score, grade_letter, grade_point, remarks) VALUES
(1, 95.00, 'A',  4.00, 'Excellent performance'),
(3, 82.00, 'B+', 3.50, NULL),
(4, 87.00, 'A-', 3.75, NULL),
(6, 77.00, 'B',  3.00, 'Solid first semester'),
(7, 96.00, 'A',  4.00, 'Top of the class'),
(8, 72.00, 'B-', 2.75, NULL),
(9, 67.00, 'C+', 2.50, 'Needs to attend more labs');

