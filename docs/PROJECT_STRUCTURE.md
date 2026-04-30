# Project Structure

This project keeps browser-accessible PHP pages inside role/module folders. The repository root is reserved for project metadata, Apache configuration, the service worker, and the PWA manifest.

```text
town_issues/
+-- app/
|   +-- Core/              # Core PHP classes such as Database and Auth
|   +-- Services/          # Domain services such as ward detection
|   +-- Support/           # Shared helper modules such as mail and password reset
+-- admin/                 # Admin dashboards and volunteer administration pages
+-- auth/                  # Login, registration, OTP, reset, and logout endpoints
+-- assets/
|   +-- css/               # Page stylesheets
|   +-- images/            # Static images and PWA icons
|   +-- js/                # Browser scripts
+-- data/                  # GeoJSON boundary files
+-- database/              # SQL schema and seed/dump files
+-- docs/                  # Project documentation
+-- PHPMailer/             # Bundled mail library
+-- public/                # Public landing page, policy pages, feedback, and unauthorized page
+-- reports/               # Report JSON endpoints and upload storage
|   +-- uploads/           # Uploaded report images
+-- tools/
|   +-- maintenance/       # CLI-only scripts for debugging/backfills
+-- user/                  # Citizen dashboard, reports, profile, password, and help pages
+-- volunteers/            # Volunteer registration, dashboard, profile, and task pages
+-- manifest.json / sw.js  # PWA files served from the app root
```

## Conventions

- Put reusable PHP classes in `app/`, not at the web root.
- Keep full page implementations in their role folders (`user/`, `admin/`, `volunteers/`).
- Use direct module endpoints instead of root wrapper PHP files.
- Keep API endpoints that JavaScript already calls in `reports/`.
- Put one-off database maintenance and debugging scripts in `tools/maintenance/`.
- Put SQL dumps and schema files in `database/`.
- Do not commit `.env` or uploaded report images.

## Include Paths

PHP pages and endpoints include reusable code directly from `app/` using `__DIR__`-based paths.
