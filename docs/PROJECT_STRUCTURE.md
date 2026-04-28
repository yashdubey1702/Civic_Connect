# Project Structure

This project keeps browser-accessible entry files at the repository root so current XAMPP URLs continue to work.

```text
town_issues/
+-- app/
|   +-- Core/              # Core PHP classes such as Database and Auth
|   +-- Services/          # Domain services such as ward detection
|   +-- Support/           # Shared helper modules such as mail and password reset
+-- assets/
|   +-- css/               # Page stylesheets
|   +-- images/            # Static images and PWA icons
|   +-- js/                # Browser scripts
+-- data/                  # GeoJSON boundary files
+-- database/              # SQL schema and seed/dump files
+-- docs/                  # Project documentation
+-- PHPMailer/             # Bundled mail library
+-- reports/               # Report JSON endpoints and upload storage
|   +-- uploads/           # Uploaded report images
+-- tools/
|   +-- maintenance/       # CLI-only scripts for debugging/backfills
+-- *.php / index.html     # Public pages served by XAMPP
```

## Conventions

- Put reusable PHP classes in `app/`, not at the web root.
- Keep page controllers and current route files at the root unless Apache routing is changed.
- Keep API endpoints that JavaScript already calls in `reports/`.
- Put one-off database maintenance and debugging scripts in `tools/maintenance/`.
- Put SQL dumps and schema files in `database/`.
- Do not commit `.env` or uploaded report images.

## Include Paths

PHP pages and endpoints include reusable code directly from `app/` using `__DIR__`-based paths.
