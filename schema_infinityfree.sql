-- =====================================================================
-- Integrated Digital Education Monitoring System (AEDMS)
-- Case Study: Albert Academy, Freetown, Sierra Leone
-- Database Schema (MySQL)
-- =====================================================================


-- ---------------------------------------------------------------------
-- USERS: one row per login account, for every actor in the system
-- Roles: admin, teacher, staff, pupil
-- ---------------------------------------------------------------------
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(120)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('admin','teacher','staff','pupil') NOT NULL,
    phone         VARCHAR(30)   NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLASSES: e.g. Form 1A, Form 2B
-- ---------------------------------------------------------------------
CREATE TABLE classes (
    class_id      INT AUTO_INCREMENT PRIMARY KEY,
    class_name    VARCHAR(50) NOT NULL UNIQUE,
    class_teacher_id INT NULL,           -- form/class teacher (links to users.user_id where role='teacher')
    CONSTRAINT fk_classes_teacher FOREIGN KEY (class_teacher_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SUBJECTS: subject taught within a class by a teacher
-- ---------------------------------------------------------------------
CREATE TABLE subjects (
    subject_id    INT AUTO_INCREMENT PRIMARY KEY,
    subject_name  VARCHAR(80) NOT NULL,
    class_id      INT NOT NULL,
    teacher_id    INT NULL,              -- links to users.user_id where role='teacher'
    CONSTRAINT fk_subjects_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    CONSTRAINT fk_subjects_teacher FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PUPILS: extends a 'pupil' user with school-specific data
-- ---------------------------------------------------------------------
CREATE TABLE pupils (
    pupil_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL UNIQUE,
    admission_no  VARCHAR(30) NOT NULL UNIQUE,
    class_id      INT NOT NULL,
    guardian_name    VARCHAR(100) NULL,
    guardian_contact VARCHAR(30)  NULL,
    date_of_birth    DATE NULL,
    gender           ENUM('M','F') NULL,
    CONSTRAINT fk_pupils_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_pupils_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STAFF: extends a 'teacher' or 'staff' user with employment data
-- ---------------------------------------------------------------------
CREATE TABLE staff (
    staff_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL UNIQUE,
    staff_no      VARCHAR(30) NOT NULL UNIQUE,
    position      VARCHAR(80) NULL,       -- e.g. Mathematics Teacher, Bursar, Records Officer
    department    VARCHAR(80) NULL,
    date_joined   DATE NULL,
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STUDENT ATTENDANCE: marked daily by a teacher, per pupil
-- ---------------------------------------------------------------------
CREATE TABLE student_attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    pupil_id      INT NOT NULL,
    class_id      INT NOT NULL,
    attendance_date DATE NOT NULL,
    status        ENUM('present','absent','late') NOT NULL,
    remarks       VARCHAR(150) NULL,
    marked_by     INT NOT NULL,           -- users.user_id (teacher)
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_satt_pupil FOREIGN KEY (pupil_id) REFERENCES pupils(pupil_id) ON DELETE CASCADE,
    CONSTRAINT fk_satt_class FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    CONSTRAINT fk_satt_teacher FOREIGN KEY (marked_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY uq_pupil_date (pupil_id, attendance_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- STAFF ATTENDANCE: daily clock-in/out or marked status for teachers/staff
-- ---------------------------------------------------------------------
CREATE TABLE staff_attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id      INT NOT NULL,
    attendance_date DATE NOT NULL,
    status        ENUM('present','absent','late','on_leave') NOT NULL,
    time_in       TIME NULL,
    time_out      TIME NULL,
    remarks       VARCHAR(150) NULL,
    marked_by     INT NOT NULL,           -- users.user_id (admin, or self)
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stat_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    CONSTRAINT fk_stat_marker FOREIGN KEY (marked_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    UNIQUE KEY uq_staff_date (staff_id, attendance_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACADEMIC PERFORMANCE: scores per pupil, subject, term
-- ---------------------------------------------------------------------
CREATE TABLE academic_records (
    record_id     INT AUTO_INCREMENT PRIMARY KEY,
    pupil_id      INT NOT NULL,
    subject_id    INT NOT NULL,
    academic_year VARCHAR(9)  NOT NULL,   -- e.g. '2025/2026'
    term          ENUM('Term 1','Term 2','Term 3') NOT NULL,
    ca_score      DECIMAL(5,2) NULL,      -- continuous assessment
    exam_score    DECIMAL(5,2) NULL,
    total_score   DECIMAL(5,2) GENERATED ALWAYS AS (IFNULL(ca_score,0) + IFNULL(exam_score,0)) STORED,
    grade         VARCHAR(5)  NULL,
    remarks       VARCHAR(150) NULL,
    recorded_by   INT NOT NULL,           -- users.user_id (teacher)
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_acad_pupil   FOREIGN KEY (pupil_id)   REFERENCES pupils(pupil_id)     ON DELETE CASCADE,
    CONSTRAINT fk_acad_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    CONSTRAINT fk_acad_teacher FOREIGN KEY (recorded_by) REFERENCES users(user_id)      ON DELETE RESTRICT,
    UNIQUE KEY uq_pupil_subject_term (pupil_id, subject_id, academic_year, term)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RESPONSIBILITIES: admin assigns duties to teachers/staff
-- ---------------------------------------------------------------------
CREATE TABLE responsibilities (
    responsibility_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id      INT NOT NULL,
    title         VARCHAR(120) NOT NULL,   -- e.g. "Head of Mathematics Department"
    description   TEXT NULL,
    date_assigned DATE NOT NULL,
    due_date      DATE NULL,
    status        ENUM('active','completed','withdrawn') NOT NULL DEFAULT 'active',
    assigned_by   INT NOT NULL,            -- users.user_id (admin)
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resp_staff  FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    CONSTRAINT fk_resp_admin  FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACTIVITY LOG: simple audit trail (useful for Chapter 4 evaluation)
-- ---------------------------------------------------------------------
CREATE TABLE activity_log (
    log_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    action        VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA: sample classes only.
-- The first ADMIN account is created by running install/setup_admin.php
-- once in your browser after importing this schema (see README.md).
-- Do NOT hand-type a password hash here — always let PHP's own
-- password_hash() generate it on your server so it is guaranteed correct.
-- =====================================================================
INSERT INTO classes (class_name) VALUES ('Form 1A'), ('Form 2A'), ('Form 3A');
