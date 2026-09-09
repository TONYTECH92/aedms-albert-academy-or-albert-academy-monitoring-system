# Integrated Digital Education Monitoring System (AEDMS)
### Case Study: Albert Academy, Freetown, Sierra Leone
Dissertation project — IMATT Colllege 
## 1. What this system does

A role-based web application with three actor types, matching your dissertation scope:

| Actor | Can do |
|---|---|
| **System Admin** | Create/manage all user accounts, manage classes & subjects, mark staff attendance, assign staff responsibilities, view school-wide dashboard |
| **Teacher / Staff** | Mark student attendance for their classes, enter CA & exam scores, view assigned responsibilities, view their own class dashboard |
| **Pupil** | View own attendance history and academic results/dashboard |

Core features implemented: **student attendance**, **academic performance recording**, **teacher/staff attendance**, **responsibility assignment**, and **role-based dashboards**.

## 2. Tech stack

- PHP 8.x (plain PHP, no framework — easier to explain/defend in your viva)
- MySQL 8.x (via PDO with prepared statements — protects against SQL injection)
- Sessions for authentication; `password_hash()` / `password_verify()` for secure password storage
- No external dependencies — runs on any standard LAMP/XAMPP/WAMP setup

## 3. Folder structure

```
aedms/
├── config/db.php              -> database connection settings (EDIT THIS)
├── includes/                  -> auth.php, functions.php, header.php, footer.php
├── assets/css/style.css       -> all styling
├── sql/schema.sql             -> full database schema (run this first)
├── install/setup_admin.php    -> one-time admin account creator (delete after use)
├── admin/                     -> admin portal pages
├── teacher/                   -> teacher/staff portal pages
├── pupil/                     -> pupil portal pages
├── index.php, login.php, logout.php
```

## 4. Setup instructions (XAMPP/local example)

1. Copy the whole `aedms/` folder into your web server root (e.g. `htdocs/aedms` for XAMPP).
2. Open phpMyAdmin (or the `mysql` CLI) and run the contents of `sql/schema.sql`. This creates the `aedms` database, all tables, and 3 sample classes.
3. Open `config/db.php` and set `$DB_USER` / `$DB_PASS` to match your MySQL setup (XAMPP default is usually user `root`, empty password).
4. In your browser, go to `http://localhost/aedms/install/setup_admin.php` and create your admin account (your real name, email, and a password you choose).
5. **Delete `install/setup_admin.php`** (or the whole `install/` folder) right after — leaving it live would let anyone create an admin account.
6. Go to `http://localhost/aedms/login.php` and log in with the admin account you just created.

## 5. Recommended order of use (matches your data collection plan)

1. Log in as admin → **Classes & Subjects**: add any classes/subjects beyond the 3 samples, and assign subjects to teachers.
2. **Manage Users**: create teacher/staff and pupil accounts (you can bulk-create these from your questionnaire/interview visit data).
3. Log in as a teacher → **Mark Attendance** and **Enter Scores** for their class.
4. Log in as admin → **Staff Attendance** to mark daily staff attendance, and **Responsibilities** to assign duties.
5. Log in as a pupil → view **My Attendance** and **My Results**.
6. Return to the admin **Dashboard** to see aggregated stats — this is your best screenshot source for Chapter Four (system testing/results).

## 6. Security notes worth mentioning in your methodology/design chapter

- Passwords are never stored in plain text (bcrypt via PHP's `password_hash()`).
- All database queries use PDO prepared statements (protection against SQL injection).
- All output is escaped with `htmlspecialchars()` (protection against XSS).
- Access to each page is enforced server-side by role (`require_role()`), not just hidden in the menu.
- Sessions regenerate their ID on login to reduce session-fixation risk.

## 7. Suggested diagrams for Chapter Three/Four

Based on this build, you should produce (I can generate these next if you want):
- Entity-Relationship Diagram (ERD) — from `sql/schema.sql`
- Use Case Diagram — 3 actors × their permitted actions (table in Section 1 above maps directly)
- System Architecture Diagram — 3-tier: Browser → PHP (business logic) → MySQL
- Data Flow Diagram (DFD Level 0/1)

## 8. Known simplifications (worth stating as "delimitations" if asked)

- One teacher per subject/class (no co-teaching).
- A pupil/teacher has exactly one login; no self-registration (admin creates all accounts, matching real school governance).
- No SMS/email notifications wired up yet (flagged as a "future work" item in several of your reviewed papers — could be a nice addition to Chapter 5 recommendations).
