# Salon Scheduler — CLAUDE.md

## Behavioral Guidelines

**Tradeoff:** These guidelines bias toward caution over speed. For trivial tasks, use judgment.

### Think Before Coding

Don't assume. Don't hide confusion. Surface tradeoffs.

Before implementing:
- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them — don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

### Simplicity First

Minimum code that solves the problem. Nothing speculative.

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

### Surgical Changes

Touch only what you must. Clean up only your own mess.

When editing existing code:
- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it — don't delete it.

When your changes create orphans:
- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

### Goal-Driven Execution

Define success criteria. Loop until verified.

Transform tasks into verifiable goals:
- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"

---

## What This Is
A salon appointment scheduling web app. **The active version is the PHP/MySQL port in `php/`** — `php/index.php` is the calendar UI (same client-side JS as the old single-file app) and it talks to `php/api.php`, which reads/writes a MySQL database via PDO. Runs on any PHP host (PHP 7.4+ with PDO MySQL); **GitHub Pages will not work** because it only serves static files.

The legacy Supabase single-file version (`index.html`) and `barcode.html` are kept at the repo root but are **not** the active app. `barcode.html` was never ported and still uses Supabase.

## Layout
- `php/index.php` — frontend (calendar UI). Fetches from `api.php`.
- `php/api.php` — JSON API. Actions: `load`, `upsertAppts`, `deleteAppt`, `saveAvail`. Tables are auto-created on first load (`ensureSchema`).
- `php/config.php` — MySQL host/name/user/password + the PDO connection. Holds placeholder credentials in the repo; fill in per host.
- `php/README.php.md` — setup notes for the PHP version.
- `index.html`, `barcode.html` — legacy Supabase versions (root).

## URLs
- **GitHub repo**: https://github.com/sylphisus/salon.git
- **Legacy Supabase site** (old `index.html`): https://sylphisus.github.io/salon
- **Legacy Supabase project**: https://japgfltuvdteiuydgumy.supabase.co

## Stack
- Pure HTML/CSS/JS frontend — no framework, no build step
- **PHP** (`api.php`) — JSON API layer
- **MySQL / MariaDB** — stores appointments and employee availability

## MySQL Tables
Created automatically on first load by `ensureSchema()` in `api.php`:
```sql
appointments (
  id          VARCHAR(64) PRIMARY KEY,
  client_name VARCHAR(255), service VARCHAR(64), stylist VARCHAR(64),
  `date`      VARCHAR(10),  `time`  VARCHAR(5),   duration INT,
  phone       VARCHAR(64),  notes   TEXT,
  created_at  VARCHAR(32),  updated_at VARCHAR(32), deleted_at VARCHAR(32)
)  -- deleted_at = soft-delete tombstone; load purges tombstones >30 days old

availability (
  stylist VARCHAR(64) PRIMARY KEY,
  hours   JSON  -- { "0": null (off) | [{start, end}], "1": [...], ... }
)
```

## Stylists
Emma, Uyen, Sophia, Olivia, Mia, Harper

## Services (with colors)
Head Spa (teal), Haircut (blue), Color (pink), Highlights (yellow),
Balayage (purple), Blowout (green), Perm (red), Treatment (sky),
Trim (light green), Extensions (violet), Consultation (slate)

## Key Features
- **Month/Week/Day** calendar views
- **Appointments**: click any day or time slot to add; click existing to edit/delete
- **Data loads on page open** (no live websocket sync in the PHP version — refresh to pull the latest). "NEW since your last visit" highlights and "DELETED" tombstones are diffed against a per-device `localStorage` timestamp on each load.
- **Employee availability**: per-stylist, per-day, multiple time blocks (e.g. 9am–1pm + 3pm–7pm)
- **Schedule overlay**: dropdown in header overlays a stylist's availability on the time grid; non-working hours get a stripe pattern, days off get a solid shade
- **Mobile-first**: responsive 2-row header, month dots, single 2D scroll container for week view (100dvh fix for iOS Safari), bottom sheet modal, swipe navigation

## Availability Data Format
```json
{
  "0": null,
  "1": [{ "start": "09:00", "end": "13:00" }, { "start": "15:00", "end": "19:00" }],
  "2": [{ "start": "09:00", "end": "18:00" }]
}
```
`null` = day off. `normalizeBlocks()` handles backward compat with old single `{start,end}` format.

## Deploy
The app runs on a PHP host (a separate machine — not this repo's GitHub Pages). To ship a change, copy the changed file(s) under `php/` onto that host, replacing the existing ones. There is no build step and no CI.

```bash
git add php/index.php   # or whichever file(s) changed
git commit -m "..."
git push
```

## Setup (fresh host)
1. Host the `php/` files on a PHP 7.4+ server with PDO MySQL.
2. `CREATE DATABASE salon CHARACTER SET utf8mb4;`
3. Edit `php/config.php` with the host's MySQL credentials.
4. Open `index.php` — tables are created automatically on first load.

## Important Notes
- `php/config.php` holds DB credentials (placeholders in the repo). Fill in per host; don't overwrite the host's copy on deploy.
- `100dvh` (not `vh`) is used for the mobile week view scroll container — fixes iOS Safari address bar clipping
- New stylists must be added in 4 places: `fStylist` select, `scheduleSelect` select, `availStylist` select, and `STYLISTS` constant
- New services must be added to `fService` select and `SVC_COLORS` object
