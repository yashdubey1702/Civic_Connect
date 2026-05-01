# Endpoint Reference

Base path: `/town_issues`

The app uses direct PHP files as endpoints. There is no framework router. The root URL `/town_issues/` serves `/town_issues/public/index.html` through `.htaccess`.

## Public Pages

| Method | Endpoint | Access | Purpose / Notes |
| --- | --- | --- | --- |
| GET | `/` | Public | Loads the public home page via `DirectoryIndex public/index.html`. |
| GET | `/public/index.html` | Public | Public landing page and community map. |
| GET | `/public/help.php` | Public | Public help page. |
| GET | `/public/contact_us.php` | Public | Contact information page. |
| GET, POST | `/public/feedback.php` | Public | Feedback form. POST fields: `csrf_token`, `full_name`, optional `email`, `topic`, `message`. |
| GET | `/public/privacy_policy.php` | Public | Privacy policy page. |
| GET | `/public/hyperlinking_policy.php` | Public | Hyperlinking policy page. |
| GET | `/public/copyright_policy.php` | Public | Copyright policy page. |
| GET | `/public/accessibility_statement.php` | Public | Accessibility statement page. |
| GET | `/public/archives.php` | Public | Archives page. |
| GET | `/public/unauthorized.php` | Public | Unauthorized access page. |

## Authentication

| Method | Endpoint | Access | Purpose / Notes |
| --- | --- | --- | --- |
| GET, POST | `/auth/login.php` | Public | Login form. POST fields: `email`, `password`, optional `remember_me`. Redirects by role after login. |
| GET, POST | `/auth/register.php` | Public | Citizen or volunteer registration. GET query: optional `account_type=volunteer`. POST fields: `account_type`, `full_name`, `email`, `password`, `confirm_password`; volunteer fields: `phone`, `address`, `ward_no`, `skills`, `availability`. |
| GET | `/auth/logout.php` | Logged-in users | Destroys the current session and redirects to the public home page. |
| GET, POST | `/auth/forget_password.php` | Public | Starts password reset. POST field: `email`. |
| GET, POST | `/auth/verify_account.php` | Reset session required | OTP verification for password reset. POST field: `otp[]` or `otp`. |
| GET | `/auth/resend_otp.php` | Reset session required | Issues and emails a new reset OTP, then redirects to verify page. |
| GET, POST | `/auth/reset_password.php` | Verified reset session required | Sets a new password. POST fields: `password`, `confirm_password`. |

## Citizen Pages

| Method | Endpoint | Access | Purpose / Notes |
| --- | --- | --- | --- |
| GET | `/user/dashboard.php` | Citizen | Citizen dashboard. |
| GET | `/user/map_reports.php` | Citizen | Map-based report submission and user report map. |
| GET | `/user/report_history.php` | Citizen | Report history, edit, and delete UI. |
| GET, POST | `/user/profile.php` | Citizen | Profile view and inline profile/password updates. GET query: optional `edit=true`, `action=change-password`. |
| GET, POST | `/user/change_password.php` | Citizen | Password change page. POST fields: `current_password`, `new_password`, `confirm_password`. |
| GET | `/user/help.php` | Citizen | Citizen help page. |

## Admin Pages

| Method | Endpoint | Access | Purpose / Notes |
| --- | --- | --- | --- |
| GET | `/admin/dashboard.php` | Super admin | Super admin dashboard. |
| GET | `/admin/municipal_dashboard.php` | Municipal admin, ward admin | Municipal or ward-scoped issue dashboard. |
| GET, POST | `/admin/assign_volunteer.php` | Any admin | Assigns an approved volunteer to a report. GET query: `report_id`. POST fields: `report_id`, `volunteer_user_id`, optional `assigned_note`. |
| GET | `/admin/volunteers.php` | Any admin | Volunteer review list. GET query: optional `status`, `error`, `success`. Ward admins are scoped to their ward. |
| GET | `/admin/volunteer_view.php` | Any admin | Volunteer profile detail. GET query: `id`, optional `error`, `success`. |
| POST | `/admin/volunteer_action.php` | Any admin | Approves or rejects a volunteer profile. POST fields: `profile_id`, `action`, optional `admin_note`. |
| GET | `/admin/volunteer_tasks.php` | Any admin | Volunteer task list. GET query: optional `status`, `ward_no`, `volunteer`, `error`, `success`. |
| GET, POST | `/admin/review_volunteer_task.php` | Any admin | Reviews completed volunteer tasks. GET query: `id`. POST fields: `task_id`, `action` (`verify` or `reject`), optional `admin_review_note`. |

## Volunteer Pages

| Method | Endpoint | Access | Purpose / Notes |
| --- | --- | --- | --- |
| GET | `/volunteers/dashboard.php` | Volunteer | Volunteer dashboard. |
| GET | `/volunteers/my_tasks.php` | Volunteer | Assigned volunteer task list. GET query: optional `status`, `error`, `success`. |
| GET | `/volunteers/task_view.php` | Volunteer | Volunteer task detail. GET query: `id`, optional `error`, `success`. |
| POST | `/volunteers/task_action.php` | Volunteer | Updates volunteer task status. POST fields: `task_id`, `action` (`accept`, `start`, `complete`), optional `note`, optional `proof_image` upload for completion. |
| GET, POST | `/volunteers/profile.php` | Volunteer | Volunteer profile view/update. POST fields: `full_name`, `phone`, `address`, `ward_no`, `skills`, `availability`. |

## Report JSON APIs

| Method | Endpoint | Access | Request Inputs | Response / Purpose |
| --- | --- | --- | --- | --- |
| POST | `/reports/submit_report.php` | Logged-in citizen session | Form data: `lat`, `lng`, `category`, optional `description`, optional `image` upload | Creates a report, detects ward, returns JSON with `success`, `message`, `ward`, `tracking_token`. |
| GET | `/reports/get_user_reports.php` | Citizen | None | Returns the logged-in citizen's reports as JSON. |
| GET | `/reports/get_reports.php` | Citizen or admin | Query: optional `search`, `page`, `per_page`, `status`, `category`, `ward` | Returns paginated reports, pagination metadata, and status counts. Citizens see their own reports; ward admins are ward-scoped. |
| GET | `/reports/get_map_reports.php` | Session-aware; public map also calls it | Query: optional `category`, `status`, `ward` | Returns map report records. Citizens and ward admins are scoped by role when logged in. |
| GET | `/reports/get_stats.php` | Any admin | None | Returns report totals and status counts. Ward admins are ward-scoped. |
| POST | `/reports/update_status.php` | Any admin | JSON body: `id`, `status` | Updates a report status. Ward admins can only update their ward reports. |
| POST | `/reports/send_notification.php` | Admin / municipal admin | JSON body: `id`, `status` | Sends an email notification about a report status update. |
| GET | `/reports/get_notifications.php` | Logged-in users | None | Returns notification count and notification list for citizen, admin, or volunteer account. |
| POST | `/reports/mark_notification_read.php` | Logged-in users | JSON body: `notification_id` | Marks a notification as read. |
| POST | `/reports/update_report.php` | Citizen owner | Form data: `csrf_token`, `id`, `category`, `description`, `lat`, `lng`, optional `image` upload | Updates one of the logged-in citizen's reports. |
| POST | `/reports/delete_report.php` | Citizen owner | JSON body: `csrf_token`, `id` | Deletes one of the logged-in citizen's reports. |
| GET, POST | `/reports/track_status.php` | Public | Query or form field: `tracking_token` | Returns report status for a token like `CC-XXXXXXXX`. |

## Public JSON / Data Files

| Method | Endpoint | Access | Purpose / Notes |
| --- | --- | --- | --- |
| GET | `/public/visitor_count.php` | Public | Increments or reads visitor count using a short-lived cookie; returns JSON. |
| GET | `/data/Wards.geojson` | Public | Ward boundary GeoJSON used by maps and ward filters. |
| GET | `/data/bmc_boundary.geojson` | Public | Bhubaneswar boundary GeoJSON used by maps. |
| GET | `/manifest.json` | Public | PWA manifest. |
| GET | `/sw.js` | Public | Service worker. |
| GET | `/reports/uploads/{filename}` | Public file path | Uploaded report image files when present. |
| GET | `/uploads/volunteer_proofs/{filename}` | Public file path | Uploaded volunteer proof image files when present. |

## Non-Web Scripts

These files are maintenance scripts, not normal browser endpoints:

| Script | Purpose |
| --- | --- |
| `tools/maintenance/update_existing_reports.php` | CLI report backfill/update script. |
| `tools/maintenance/update_reports_with_geojson.php` | CLI report ward/boundary update script. |
| `tools/maintenance/debug_municipality.php` | Maintenance/debug utility. |
