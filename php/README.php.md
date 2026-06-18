# Salon Scheduler — PHP + MySQL version

A PHP/MySQL port of the salon scheduler. The calendar UI (`index.php`) is the same
client-side JavaScript app; the data layer that used to talk directly to Supabase now
talks to `api.php`, which reads/writes a MySQL database.

> Live multi-device sync (the old Supabase websocket) is **not** included in this version —
> data loads when the page opens. Refresh the browser to pull the latest.

## Files
- `index.php` — the frontend (calendar UI). Fetches from `api.php`.
- `api.php` — JSON API: `load`, `upsertAppts`, `deleteAppt`, `saveAvail`.
- `config.php` — DB host/name/user/password + the PDO connection.

## Setup
1. Host these files on any PHP server (PHP 7.4+ with PDO MySQL). **GitHub Pages will not work** — it only serves static files.
2. Create the database:
   ```sql
   CREATE DATABASE salon CHARACTER SET utf8mb4;
   ```
   The `appointments` and `availability` tables are created automatically the first time the page loads (`ensureSchema` in `api.php`).
3. Edit `config.php` with your MySQL host, database name, username, and password.
4. Open `index.php` in a browser.

## Notes
- The "NEW since your last visit" highlight and "DELETED" tombstones still work — they
  diff against a per-device `localStorage` timestamp each time the page loads.
- The old `index.html` (Supabase version) and `barcode.html` are left untouched.
  `barcode.html` still uses Supabase and was not part of this port.
