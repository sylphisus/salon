# Salon Scheduler — CLAUDE.md

## What This Is
A single-file salon appointment scheduling web app (`index.html`). No build step. Hosted on GitHub Pages, database on Supabase.

## URLs
- **Live site**: https://sylphisus.github.io/salon
- **GitHub repo**: https://github.com/sylphisus/salon.git
- **Supabase project**: https://japgfltuvdteiuydgumy.supabase.co

## Stack
- Pure HTML/CSS/JS — single `index.html`, no framework, no build step
- **Supabase** (Postgres) — stores appointments and employee availability
- **GitHub Pages** — serves the static file

## Supabase Tables
```sql
appointments (
  id text PRIMARY KEY,
  client_name text, service text, stylist text,
  date text, time text, duration integer,
  phone text, notes text, created_at timestamptz
)

availability (
  stylist text PRIMARY KEY,
  hours jsonb  -- { "0": null (off) | [{start, end}], "1": [...], ... }
)
```
Both tables have RLS disabled.

## Stylists
Emma, Uyen, Sophia, Olivia, Mia, Harper

## Services (with colors)
Head Spa (teal), Haircut (blue), Color (pink), Highlights (yellow),
Balayage (purple), Blowout (green), Perm (red), Treatment (sky),
Trim (light green), Extensions (violet), Consultation (slate)

## Key Features
- **Month/Week/Day** calendar views
- **Appointments**: click any day or time slot to add; click existing to edit/delete
- **Real-time sync**: Supabase postgres_changes subscription — all open devices update instantly
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
```bash
cd ~/Documents/salon
git add index.html
git commit -m "..."
git push
```
GitHub Pages auto-deploys on push to `main`. No CI needed.

## Important Notes
- Supabase publishable key is embedded in the HTML — this is intentional for an internal tool
- `100dvh` (not `vh`) is used for the mobile week view scroll container — fixes iOS Safari address bar clipping
- New stylists must be added in 4 places: `fStylist` select, `scheduleSelect` select, `availStylist` select, and `STYLISTS` constant
- New services must be added to `fService` select and `SVC_COLORS` object
