# ReTool Campus — Peer-to-Peer Campus Tool & Equipment Library

A dynamic, database-driven web app built for **CSE 3120 (Web Programming)** —
Open-Ended Lab. Stack: **HTML, CSS, JavaScript, PHP, MySQL**.

Students and staff list underused items (electronics kits, cameras, lab
tools) and borrow from each other instead of buying new — reducing e-waste
and campus spending.

## Features (mapped to the lab's task list)

- **Task 1 — Functionality:** browse/search/filter items, item detail pages,
  borrow-request workflow (request → approve/decline → return), user
  dashboard, profile & password management, image uploads.
- **Task 2 — Database design:** normalized MySQL schema — `users`,
  `categories`, `items`, `borrow_requests`, `impact_log` — see
  `sql/schema.sql` (also review `docs/ER-notes.md` for the design rationale
  you can paste into your report).
- **Task 3 — Codebase:** plain PHP (no framework) with PDO + prepared
  statements, a session-based authentication system, CSRF protection on
  every form, and server-side validation on every input.
- **Task 4 — Report:** use this README + the ER notes as a starting point;
  remember to add your own screenshots, testing notes, and citations.

## Tech / architecture

- **Frontend:** semantic HTML, hand-written CSS (`css/style.css`) — a
  "workshop pegboard" design system — and a small vanilla JS file
  (`js/main.js`) for client-side UX only (server always re-validates).
- **Backend:** PHP 8, PDO/MySQL, `password_hash()`/`password_verify()`,
  session-based auth, CSRF tokens on all state-changing forms.
- **Database:** MySQL/MariaDB, see `sql/schema.sql`.

## Folder structure

```
campus-tool-library/
├── config/db.php          # DB connection (edit credentials here)
├── includes/              # auth.php, functions.php, header.php, footer.php
├── css/style.css
├── js/main.js
├── sql/schema.sql          # run this once to create + seed the database
├── uploads/items/          # uploaded item photos land here (writable)
├── index.php                # browse / search / filter
├── register.php / login.php / logout.php
├── add_item.php             # create a listing (auth required)
├── item.php                 # item detail + borrow request form
├── dashboard.php             # my listings / incoming / outgoing requests
├── request_action.php        # approve / decline / return / cancel (POST only)
└── profile.php                # edit account, change password
```

## Setup (XAMPP / WAMP / MAMP)

1. Copy the `campus-tool-library` folder into your server's web root
   (e.g. `htdocs/` for XAMPP).
2. Start Apache and MySQL from your control panel.
3. Open **phpMyAdmin**, click **Import**, and import `sql/schema.sql`.
   This creates the `campus_tool_library` database, all tables, and a
   couple of demo listings.
4. Open `config/db.php` and confirm `DB_USER` / `DB_PASS` match your MySQL
   setup (XAMPP default is user `root`, empty password — already set).
5. Visit `http://localhost/campus-tool-library/` in your browser.
6. Click **Sign up** to create a real account, or log in with a demo
   account seeded by the schema:
   - `ayesha@ulab.edu.bd` / `Passw0rd!`
   - `tanvir@ulab.edu.bd` / `Passw0rd!`
7. Make sure `uploads/items/` is writable by the web server if you want to
   upload photos (`chmod 775 uploads/items` on Linux/macOS).

## Security notes worth mentioning in your report

- Passwords are never stored in plain text (`password_hash` / bcrypt).
- All SQL uses PDO **prepared statements** — no string-concatenated queries,
  which prevents SQL injection.
- Every POST form includes a **CSRF token** checked with `hash_equals()`.
- All output is escaped with `htmlspecialchars()` (the `e()` helper) to
  prevent stored/reflected XSS.
- File uploads are validated by real MIME type (`finfo`), size-limited, and
  renamed randomly on save to avoid path/overwrite attacks.
- Ownership checks (`owner_id == session user`) gate every edit/delete/
  approve/reject action so one user can't act on another's data.

## Ideas for extending it further

- Email notifications when a request is approved/declined.
- Star ratings / reviews after a completed loan.
- Admin panel to moderate listings.
- Overdue-loan reminders using a cron job.
