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

## Vercel Deployment

This repo includes `vercel.json` and `api/index.php` so Vercel can run the existing PHP files through the `vercel-php` community runtime. The routing also keeps the existing `/town_issues/...` links working on a root Vercel domain.

1. Use a hosted MySQL-compatible database. Vercel cannot connect to your XAMPP `localhost` database.
2. Import `database/civicconnect_import.sql` into that database.
3. In Vercel Project Settings, set these environment variables:

```text
APP_RUNTIME=vercel
SESSION_DRIVER=database
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-database-user
DB_PASSWORD=your-database-password
DB_CHARSET=utf8mb4
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=your-smtp-user
SMTP_PASSWORD=your-smtp-password
SMTP_FROM_EMAIL=your-sender-email
SMTP_FROM_NAME=CivicConnect
```

4. Deploy with Vercel using Framework Preset `Other` and no custom build command.

Notes:

- Vercel's filesystem is not persistent. Report images and volunteer proof uploads need external object storage for production use.
- `app_sessions` is included in the SQL import so login sessions work across serverless invocations.

## Maintenance Scripts

Run maintenance scripts from the command line only, for example:

```powershell
php tools/maintenance/update_existing_reports.php
```
