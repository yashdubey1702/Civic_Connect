# Civic Connect

Civic Connect is a PHP/XAMPP application for reporting and managing town issues with ward-based location detection.

## Project Structure

See [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md) for the full layout.

Key folders:

- `app/` - reusable PHP classes and helper modules.
- `assets/` - CSS, JavaScript, and images.
- `reports/` - report API endpoints and uploaded report images.
- `data/` - GeoJSON boundary data.
- `database/` - single SQL import file.
- `tools/maintenance/` - CLI-only backfill/debug scripts.

## Local Setup

1. Put the project under your XAMPP `htdocs` directory.
2. Import `database/civicconnect_import.sql` into MySQL.
3. Keep local secrets in `.env`; this file is intentionally ignored by Git.
4. Open `http://localhost/town_issues/` or `http://localhost/town_issues/public/index.html` in your browser.

Starter admin login after importing the database:

- Email: `superadmin@bmc.gov.in`
- Password: `password`

## Maintenance Scripts

Run maintenance scripts from the command line only, for example:

```powershell
php tools/maintenance/update_existing_reports.php
```
