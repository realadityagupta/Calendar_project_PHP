# 🗓️ Course Calendar — PHP + MySQL Appointment Scheduler
 
A single-page, server-rendered calendar app for scheduling course/instructor appointments. Built with vanilla PHP (procedural, no framework), MySQL, and plain JavaScript — a classic full-stack CRUD app without any client-side framework or build step.
 
---
 
## ✨ Features
 
- **Monthly calendar view** — auto-generated grid for any month, with day names, correct blank offset for the 1st weekday, and today's date highlighted.
- **Month navigation** — `❮` / `❯` buttons step the calendar forward/backward without a page reload (pure client-side re-render).
- **Add appointments** — click "+ Add" on any day to open a modal pre-filled with that date; enter course title, instructor, start/end date, start/end time.
- **Multi-day events** — an appointment spanning several days is automatically expanded and shown on every date in its range.
- **Edit appointments** — click "✏️ Edit" on a day with existing events; if multiple events fall on that day, a dropdown lets you pick which one to edit, and the form auto-fills with its data.
- **Delete appointments** — a confirm-guarded delete button removes the appointment from the database.
- **Live digital clock** — current time (HH:MM:SS), date, and weekday, updating every second.
- **Flash success/error messages** — shown after add/edit/delete, auto-fading and removing themselves after 5 seconds.
- **Custom styling** — CSS variables for a consistent color theme, Google Fonts (Outfit, Ubuntu).
---
 
## 🤔 How It Works / Why This Design
 
- **Server renders the page, JS renders the calendar**: PHP fetches all appointments once on page load and dumps them into the page as a JSON array (`const events = <?= json_encode(...) ?>`). From there, all calendar rendering, navigation, and modal handling happens client-side in `calendar.js` with zero extra network requests — simple and fast for a small dataset, at the cost of needing a full page reload after every add/edit/delete (PHP handles the mutation, then redirects back).
- **Multi-day events are pre-expanded server-side**: rather than teaching the JS calendar to understand date ranges, `calendar.php` loops from `start_date` to `end_date` and inserts a copy of the event for each day. This keeps the frontend's per-day lookup (`events.filter(e => e.date === dateStr)`) trivially simple.
- **Prepared statements everywhere**: every INSERT/UPDATE/DELETE uses `mysqli` prepared statements with bound parameters — protects against SQL injection even though there's no other input sanitization layer.
- **POST + redirect (PRG pattern)**: every form submits via POST, and on success/failure the server responds with a `Location` redirect carrying a `?success=N` or `?error=N` flag. This avoids the classic "resubmit form on refresh" browser warning.
- **No framework, no build step**: plain PHP + vanilla JS keeps the project easy to run on any basic PHP/MySQL stack (e.g., XAMPP/MAMP) with no `npm install` or compilation required — appropriate for a small coursework/demo project.
---
 
## 📋 Requirements
 
- PHP 8+ (uses `match` expressions and named nullsafe-style `??` throughout)
- MySQL / MariaDB
- A local PHP dev stack such as **XAMPP** or **MAMP** (the DB connection is hardcoded to `localhost`)
- A web browser
### Database setup
 
Create a database named `calendar` with an `appointments` table matching the fields used in `calendar.php`:
 
```sql
CREATE DATABASE IF NOT EXISTS calendar;
USE calendar;
 
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    instructor_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
);
```
 
> Note: the repo contains two DB-connection files — `connection.php` (the one actually `include`d by `calendar.php`, credentials: user `root`, empty password, db `calendar`) and `config.php` (an equivalent but currently unused alternative). Only `connection.php` needs to be correct/used; keep both in sync if you edit credentials, or remove the unused one.
 
### Run locally
 
1. Place the project folder inside your XAMPP/MAMP `htdocs` directory.
2. Start Apache and MySQL from the XAMPP/MAMP control panel.
3. Create the `calendar` database and `appointments` table (see SQL above) via phpMyAdmin or the MySQL CLI.
4. Visit `http://localhost/Calendar_project_PHP/index.php` in your browser.
---
 
## 🧱 Tech Stack
 
| Layer | Technology |
|---|---|
| Server language | PHP 8 (procedural) |
| Database | MySQL (via `mysqli`, prepared statements) |
| Frontend rendering | Vanilla JavaScript (DOM APIs, no framework) |
| Styling | Plain CSS (CSS custom properties), Google Fonts |
| Local server | XAMPP / MAMP (Apache + MySQL) |
 
---
 
## 🏗️ Architecture & System Flow
 
### File map
 
```
index.php        → HTML shell + includes calendar.php; embeds events as JSON; loads calendar.js
calendar.php      → All backend logic: handles add/edit/delete POSTs, fetches & expands events
connection.php    → mysqli connection actually used by calendar.php
config.php        → Unused duplicate DB connection (not included anywhere)
calendar.js       → Renders the calendar grid, handles modal open/edit/close, live clock
style.css         → Visual styling, theme colors, layout
```
 
### Request flow
 
**Page load**
1. Browser requests `index.php`.
2. `index.php` includes `calendar.php`, which:
   - Connects to MySQL via `connection.php`.
   - Checks if the request is a POST add/edit/delete action (see below) — on a plain page load, it isn't, so it skips to fetching.
   - Runs `SELECT * FROM appointments`, and for every row whose date range spans multiple days, expands it into one entry per date into `$eventsFromDB`.
3. `index.php` renders the HTML shell (header, clock placeholders, calendar container, hidden modal/form) and injects `$eventsFromDB` into the page as `const events = [...]` (JSON).
4. `calendar.js` loads, calls `renderCalendar()` to build the current month's grid from `events`, and starts the live clock (`setInterval`, 1s).
**Adding/editing/deleting an appointment**
1. User clicks "+ Add" or "✏️ Edit" on a calendar day → `calendar.js` populates and shows the modal (`openModalForAdd` / `openModalForEdit`), setting a hidden `action` field to `"add"` or `"edit"`.
2. User submits the form → a normal HTML POST to `index.php` (same page) with `action=add|edit|delete` plus the form fields.
3. `calendar.php` (included before any HTML output) detects the matching `action`, validates required fields, runs the appropriate prepared-statement query (`INSERT`, `UPDATE`, or `DELETE`), then issues a `header("Location: ...?success=N")` redirect — this ends the request before any HTML is rendered.
4. The browser follows the redirect back to `index.php` (GET), which re-runs the fetch/expand step with the now-updated data and displays the corresponding success message before it auto-fades after 5 seconds.
### Data mapping
 
```
MySQL `appointments` row
  { id, course_name, instructor_name, start_date, end_date, start_time, end_time }
        │
        │  (calendar.php expands multi-day ranges, one entry per date)
        ▼
PHP $eventsFromDB[] entry
  { id, title: "course - instructor", date, start, end, start_time, end_time }
        │
        │  (json_encode into the page)
        ▼
JS `events` array
        │
        │  events.filter(e => e.date === cellDateStr)
        ▼
Rendered <div class="event"> blocks inside each calendar day cell
```
 
---
 
## 🔒 Notes & Limitations
 
- SQL injection is mitigated via prepared statements, but there's no server-side validation beyond "field is non-empty" (e.g., no check that `end_date` ≥ `start_date`, no XSS-escaping of user text when echoed back into the page/JS).
- DB credentials are hardcoded in `connection.php`/`config.php` rather than pulled from environment variables — fine for local coursework use, but should be externalized before any real deployment.
- Every mutation triggers a full page reload; there's no AJAX/fetch-based partial update.
---
 
## 🏁 Conclusion
 
This project is a compact, dependency-free demonstration of classic PHP web development: a single PHP script handles all CRUD operations against MySQL using safe prepared statements, following the POST/redirect/GET pattern to avoid duplicate submissions, while a small vanilla-JS layer turns the fetched data into an interactive, navigable monthly calendar with add/edit/delete modals — all without any frontend framework, bundler, or backend framework overhead.
