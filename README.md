# Riverside College — Student Management System

## Overview

A real, working, database-driven Student Management System for a college registrar's
office — not a mockup. It manages students, teaching staff, departments, courses,
enrollments, grades, and academic transcripts behind a secure admin login, alongside a
public marketing site (Home, About, Contact). Built with plain HTML5, CSS3, vanilla
JavaScript, and PHP 8+ on MySQL via PDO — no frameworks, so it drops straight into XAMPP.

## Features

- **Role-based access control** — four roles (Super Admin, Admin/Registrar, Teacher, Student),
  enforced server-side on every single page, not just hidden buttons. Teacher and Student get
  their own separate, ownership-scoped portals. See "Role-Based Access Control" below.
- **Secure authentication** — bcrypt password hashing, CSRF-protected forms, session
  regeneration on login, protected admin pages, no exposed SQL errors.
- **Live dashboard** — every number and chart is a real query: total students, teachers,
  departments, courses, enrollments, active students, and a university-wide GPA; an
  enrollment-by-admission-year chart, a gender distribution chart, and a course
  registrations-by-semester chart (Chart.js); a department summary table; recent students;
  a merged recent-activity feed; quick-action shortcuts.
- **Student management** — full CRUD, photo upload (validated by real MIME type, not just
  extension), search, filters (department/status/year), pagination, delete confirmation.
- **Teacher management** — full CRUD, photo upload, department assignment, search, filters,
  shows the courses each teacher is assigned to.
- **Department management** — full CRUD, live student/teacher/course counts, safe deletion
  (dependent records are unassigned via `ON DELETE SET NULL`, never silently deleted).
- **Course management** — full CRUD, department/teacher assignment, credit-hour validation,
  search, filters (department/semester/status).
- **Enrollment system** — enroll a student in a course for a given academic year and
  semester; duplicate enrollment is blocked both in the UI and by a database unique
  constraint; search/filter by student, course, semester, and academic year.
- **Grades & GPA, calculated automatically** — enter a numeric score (0–100) against any
  ungraded enrollment; the letter grade and grade point are derived automatically from a
  single, easy-to-edit score-to-grade scale (`scoreToGrade()` in `includes/functions.php`) —
  no one ever picks a letter grade by hand, and nothing is hard-coded per record. Semester
  and cumulative GPA are calculated live from stored scores every time they're displayed.
- **Safe deletion** — deleting a Student or Course with existing enrollment/grade history is
  blocked with a clear explanation, instead of silently cascading that academic record away;
  the suggested fix (set status to Inactive/Graduated) is stated in the error itself.
- **Official transcript** — pick a student, get an A4-style transcript grouped by term with
  per-semester GPA and a cumulative GPA/total-credits summary, plus a working **Print**
  button with dedicated print CSS that hides the sidebar/navigation and prints just the
  transcript sheet.
- **Contact form that's honest about email** — a stock XAMPP install has no SMTP configured,
  so instead of pretending to send mail, submissions are validated and stored in a
  `contact_messages` table, reviewable from an admin **Messages** inbox (mark as read /
  delete) — nothing is silently dropped.
- **Data management UX** — every major table (Students, Teachers, Departments, Courses,
  Enrollments, Grades, Users, Messages) has search, filters, clickable sortable columns,
  pagination, a "Showing X–Y of Z" record count, and a proper empty state. Students, Courses,
  and Enrollments go further: search/filter/sort/pagination update the table in place via a
  small vanilla-JS AJAX layer (`[data-live-table]` in `app.js`) — no full page reload, no
  framework. Every link and form underneath still works with JavaScript disabled; the AJAX
  layer only intercepts what already has a working plain URL, and falls back to a normal
  page load if a fetch ever fails.
- **Fully responsive** — every page, table, and form reflows correctly from 320px up
  (collapsible sidebar drawer, collapsible public nav, stacking cards, scrollable tables).

## Technologies

- HTML5
- CSS3 (hand-written design system — no Bootstrap/Tailwind)
- Vanilla JavaScript (toasts, modals, carousel, mobile nav/sidebar, client-side validation)
- PHP 8+
- MySQL
- PDO (prepared statements everywhere user input touches a query)
- Font Awesome 6 (icons) and Chart.js (dashboard charts) via CDN

## Main Modules

Dashboard · Students · Teachers · Departments · Courses · Enrollments · Grades ·
Transcript · Contact Messages · Users & Roles · Settings · Teacher Portal · Student Portal ·
Authentication

## Installation

1. **Install XAMPP** if you haven't already, and start **Apache** and **MySQL** from the
   control panel.
2. **Copy the project** into your `htdocs` folder, so the path is:
   ```
   C:\xampp\htdocs\student-management-system
   ```
3. **Create the database.** Open phpMyAdmin at `http://localhost/phpmyadmin`, click
   **Import**, choose `database.sql` from the project folder, and click **Go**. This single
   file creates every table (`users`, `departments`, `teachers`, `courses`, `students`,
   `enrollments`, `grades`, `contact_messages`), all relationships/indexes, realistic sample
   data, and the demo admin account.
   - *If you already have an earlier version of this database* (just the first five
     tables), you can safely re-run only the statements from the
     `-- Enrollment & Academic Records extension` marker onward in `database.sql` — every
     `CREATE TABLE` there uses `IF NOT EXISTS` and nothing above that marker is touched.
4. **Configure the database connection**, if needed, in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'student_management_system');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
   These are already correct for a default XAMPP install. `BASE_URL` is detected
   automatically from the project's location — no matter how deep a page lives — so it
   works whether you install at the site root or in a subfolder.
5. **Open it in your browser**:
   ```
   http://localhost/student-management-system/
   ```

## Role-Based Access Control

Four roles, enforced server-side on every page (never just by hiding a button):

| Role | Can do |
|---|---|
| **Super Admin** | Everything Admin/Registrar can, plus manage user accounts and roles, and edit system settings. |
| **Admin / Registrar** | Manage students, teachers, departments, courses, enrollments, grades; view and print any transcript. |
| **Teacher** | View their own assigned courses and enrolled students; enter/update grades — only for their own courses. |
| **Student** | View their own profile, courses, grades, GPA, and print their own transcript — nothing else. |

Every admin page checks `requireRole('super_admin', 'admin')`; the Users and Settings
pages additionally require `requireRole('super_admin')`. Teacher and Student get their own
separate portals (`/teacher/`, `/student/`) rather than reduced views of the admin pages —
every query there is scoped server-side to the logged-in user's own `teacher_id` or
`student_id` (e.g. a teacher opening another teacher's course by guessing its ID in the URL
gets a "not found" redirect, not someone else's roster).

## Demo Accounts

Demo accounts are included in the local database for development and testing.

For security, login credentials are not published in this public repository.

## Screenshots

*(Add screenshots here before publishing — suggested shots: Home page, Dashboard, Student
list, Student form, Transcript print view.)*

```
docs/screenshots/home.png
docs/screenshots/dashboard.png
docs/screenshots/students-list.png
docs/screenshots/transcript.png
```

## Project Structure

```
student-management-system/
├── index.php                    → Public Home page (hero, carousel, mosaic, stats)
├── database.sql                 → Full schema + sample data (safe to re-run additively)
├── README.md
│
├── config/
│   └── database.php               → PDO connection + automatic BASE_URL detection
│
├── auth/
│   ├── login.php                   → bcrypt + CSRF login
│   └── logout.php                  → Session destroy + redirect
│
├── admin/                          (Super Admin + Registrar — requireRole('super_admin','admin'))
│   ├── dashboard.php               → Live stats, charts, department summary, activity feed
│   ├── students/                   → Student CRUD (index/create/edit/view/delete)
│   ├── teachers/                   → Teacher CRUD
│   ├── departments/                → Department CRUD
│   ├── courses/                    → Course CRUD
│   ├── enrollments/                → Enrollment CRUD
│   ├── grades/                     → Grade CRUD
│   ├── transcript/                 → Student picker + printable transcript
│   ├── messages/                   → Contact-form inbox (mark read / delete)
│   ├── users/                      → Manage user accounts & roles (Super Admin only)
│   └── settings/                   → Institution/branding settings (Super Admin only)
│
├── teacher/                        (Teacher — requireRole('teacher'), scoped to own courses)
│   ├── dashboard.php               → Own course/student stats
│   ├── courses.php                 → Assigned courses only
│   ├── course-students.php         → Roster for one of their own courses
│   └── grade.php                   → Enter/update a grade — ownership-checked server-side
│
├── student/                        (Student — requireRole('student'), scoped to own record)
│   ├── dashboard.php               → Own profile summary + GPA
│   ├── courses.php                 → Own enrolled courses
│   ├── grades.php                  → Own grade history + GPA
│   └── transcript.php              → Own printable transcript (no student picker)
│
├── includes/
│   ├── header.php / footer.php      → Shared <head> + closing scripts/toasts
│   ├── site-nav.php / site-footer.php → Public site header/footer
│   ├── navbar.php / sidebar.php     → Admin topbar/sidebar
│   ├── teacher-nav.php / student-nav.php → Portal sidebars for those roles
│   ├── auth_check.php               → Session, login guard, CSRF, h(), flash messages
│   ├── permissions.php              → hasRole() / requireRole() — RBAC enforcement
│   └── functions.php                → Badges, dropdowns, pagination, uploads, GPA logic
│
├── pages/
│   ├── about.php                    → Purpose, benefits, features
│   └── contact.php                  → Contact info + database-backed contact form
│
└── assets/
    ├── css/style.css                 → Full design system (incl. print stylesheet)
    ├── js/app.js                     → Toasts, modals, carousel, nav/sidebar, validation
    ├── images/                       → Local image assets
    └── uploads/                      → Student/teacher photos (script execution blocked)
```

## Security

- **PDO prepared statements** for every query that includes user input — no string-built SQL.
- **Password hashing** with `password_hash()` / `password_verify()` (bcrypt); no plain-text
  passwords anywhere.
- **CSRF protection** on every state-changing form (a per-session token checked with
  `hash_equals()`).
- **Session hardening** — HttpOnly + SameSite cookies, `session.use_strict_mode` (rejects
  client-supplied session IDs the server never issued — the standard defense against session
  fixation), the `Secure` cookie flag when served over HTTPS, session ID regenerated on login
  and again automatically every 15 minutes for long-lived sessions, and every protected page
  behind `requireRole()` (which itself calls `requireLogin()` first).
- **Row-level ownership enforcement** — Teacher and Student portal pages don't just check the
  role; every query is scoped to the logged-in user's own `teacher_id`/`student_id` server-side,
  so guessing another user's ID in the URL returns "not found," never their data.
- **Output escaping** — every piece of user-controlled data is passed through `h()`
  (`htmlspecialchars`) before it reaches HTML.
- **Secure uploads** — real MIME-type detection via `finfo` (not just the file extension),
  a 2MB size cap, randomly generated filenames, and the uploads folder blocks script
  execution outright via `.htaccess` (requires `AllowOverride` to be enabled for that
  directory in your Apache config — the default on XAMPP).
- **No leaked internals** — `display_errors` is explicitly disabled and errors are logged
  server-side instead (`config/database.php`), on top of the global exception/error handler;
  no stack traces, file paths, or credentials ever reach the browser.
- **`.gitignore`** — uploaded photos are real user data and are excluded from version control
  by default, so they're never accidentally published in a public repo.
- **Custom 404 / 403 / 500 pages** — styled, on-brand, and dependency-minimal (the 500 page in
  particular loads nothing that could itself be the reason the site is down — no database call).
  Wired via `.htaccess` `ErrorDocument` directives *(update the three paths in `.htaccess` if
  you install to a different folder name than `student-management-system`)*, and the same
  renderer is reused by the global exception handler and by a database connection failure, so
  every failure mode shows one consistent, honest page instead of three different ones.
- **Performance** — the dashboard's and public homepage's several separate `COUNT(*)` queries
  were combined into one round trip each via subselects; added indexes on `courses.status`,
  `courses.semester`, and `enrollments.status` for the columns their filter dropdowns query by.

## Limitations / honest caveats

- The contact form stores messages in the database instead of sending real email, since a
  default XAMPP install has no SMTP relay configured. Wiring up PHPMailer + a real SMTP
  account is a natural next step if this goes into production.
- This project was built and reviewed without access to a live PHP interpreter in the build
  environment — every file was checked for syntax balance and logical correctness by hand,
  but you should still run through the app end-to-end on your own XAMPP install before
  presenting or submitting it.
- Grade/enrollment academic years are free-text (validated to the `YYYY/YYYY` pattern)
  rather than a separate lookup table — simple by design for a system this size, but worth
  normalizing further in a larger deployment.
- There is a single admin role; there's no separate teacher or student login/self-service
  portal.

## Running it in XAMPP (quick recap)

1. Start Apache + MySQL in the XAMPP control panel.
2. Import `database.sql` in phpMyAdmin (once).
3. Visit `http://localhost/student-management-system/`.
4. Log in at `/auth/login.php` with the demo account above.
5. Explore the sidebar: Students → Enrollments → Grades → Transcript is the natural flow
   to see the whole academic-records pipeline connect end to end.
