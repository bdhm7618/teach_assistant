# Phase Log

## Phase 0-A — Slug-based Routing — 2026-06-07

### Created
- `modules/Core/App/Http/Middleware/IdentifyTenant.php`
- `modules/Channel/Database/Migrations/2026_06_07_000001_add_slug_status_type_to_channels_table.php`

### Modified
- `bootstrap/app.php` — registered `identify.tenant` middleware alias
- `modules/Channel/App/Scopes/ChannelScope.php` — use `app('current_channel_id')` first, auth fallback
- `modules/Channel/App/Traits/HasChannelScope.php` — same resolution on model creating
- `modules/Channel/App/Models/Channel.php` — added slug, status, trial_ends_at, type; isAccessible()
- `modules/Channel/App/Http/Controllers/V1/ChannelController.php` — slug generation on register, @OA annotations updated
- `modules/Channel/routes/api-v1.php` — public /auth/ + protected /{channel_slug}/ groups
- `modules/Academic/routes/api-v1.php` — wrapped in {channel_slug}
- `modules/Student/routes/api-v1.php` — wrapped in {channel_slug}
- `modules/Payment/routes/api-v1.php` — wrapped in {channel_slug}
- `modules/Attendance/routes/api-v1.php` — wrapped in {channel_slug}

### Namespace fixes (pre-existing bugs)
- `modules/Admin/app/Models/Admin.php`
- `modules/Admin/app/Providers/AdminServiceProvider.php`
- `modules/Admin/app/Providers/EventServiceProvider.php`
- `modules/Admin/app/Providers/RouteServiceProvider.php`
- `modules/Core/app/Providers/CoreServiceProvider.php`
- `modules/Core/app/Providers/EventServiceProvider.php`
- `modules/Core/app/Providers/RouteServiceProvider.php`
- `modules/Core/app/Http/Controllers/CoreController.php`
- `modules/Channel/app/Models/ClassModel.php`
- `modules/Channel/app/Models/Group.php` (Channel module)
- `modules/Student/app/Http/Controllers/StudentController.php`
- `modules/Payment/app/Http/Controllers/PaymentController.php`
- `modules/Academic/app/Http/Controllers/V1/GroupUserController.php` — fixed wrong base class
- `config/auth.php` — fixed Admin model reference

### Swagger
- Added `DocBlockAnnotationFactory` to l5-swagger config (swagger-php v5 requires it for @OA docblocks)
- Installed `doctrine/annotations` package
- Added `class OpenAPI {}` to `app/Http/Schemas/OpenAPI.php`
- `php artisan l5-swagger:generate` — SUCCESS

---

## Phase 0-B — Course Model — 2026-06-07

### Created
- `modules/Academic/Database/Migrations/2026_06_07_000002_create_courses_table.php`
- `modules/Academic/Database/Migrations/2026_06_07_000003_update_groups_table_for_courses.php`
- `modules/Academic/App/Models/Course.php`
- `modules/Academic/App/Repositories/CourseRepository.php`
- `modules/Academic/App/Http/Controllers/V1/CourseController.php`
- `modules/Academic/App/Http/Requests/V1/CourseRequest.php`
- `modules/Academic/App/Http/Resources/V1/CourseResource.php`

### Modified
- `modules/Academic/App/Models/Group.php` — added SoftDeletes, course_id, payment_model, starts_at, ends_at, status; course() relationship; fixed all imports
- `modules/Academic/App/Http/Requests/V1/GroupRequest.php` — course_id, payment_model, starts_at, ends_at; class_grade_id made nullable
- `modules/Academic/routes/api-v1.php` — added CourseController routes

### Migrations run
- `2026_06_07_000002_create_courses_table` ✅
- `2026_06_07_000003_update_groups_table_for_courses` ✅

### Swagger
- CourseController has full @OA\\ annotations (5 endpoints)
- `php artisan l5-swagger:generate` — SUCCESS

---

## Phase 0-C — Session Instances — 2026-06-07

### Created
- `modules/Academic/Database/Migrations/2026_06_07_000004_create_sessions_table.php` (table: `group_sessions`)
- `modules/Attendance/Database/Migrations/2026_06_07_000005_add_session_id_to_attendances_table.php`
- `modules/Academic/App/Models/Session.php` (table: `group_sessions`)
- `modules/Academic/App/Services/SessionService.php`
- `modules/Academic/App/Jobs/GenerateRecurringSessionsJob.php`
- `modules/Academic/App/Http/Controllers/V1/SessionController.php`
- `modules/Academic/App/Http/Requests/V1/SessionRequest.php`
- `modules/Academic/App/Http/Resources/V1/SessionResource.php`

### Modified
- `modules/Academic/routes/api-v1.php` — added Session routes under groups/{group}/sessions

### Notes
- Named table `group_sessions` (not `sessions`) to avoid conflict with Laravel's built-in session table

### Migrations run
- `2026_06_07_000004_create_sessions_table` ✅ (group_sessions)
- `2026_06_07_000005_add_session_id_to_attendances_table` ✅

### Swagger
- SessionController has full @OA\\ annotations (5 endpoints including recurring)
- `php artisan l5-swagger:generate` — SUCCESS

---

## Phase 1 — Complete Auth — 2026-06-08

### Created
- `modules/Core/Database/Migrations/2026_06_08_000001_add_type_to_otps_table.php`
- `modules/Channel/App/Events/PasswordResetRequested.php`
- `modules/Channel/App/Listeners/SendPasswordResetOtpListener.php`
- `modules/Channel/App/Jobs/SendPasswordResetOtpJob.php`
- `modules/Channel/App/Mail/PasswordResetOtpMail.php`
- `modules/Channel/resources/views/emails/password-reset.blade.php`

### Modified
- `modules/Core/App/Repositories/OtpRepository.php` — generate() now accepts type + expiry; added getLatestUnverified(), invalidatePrevious(), markAsVerified()
- `modules/Channel/App/Listeners/SendEmailVerificationListener.php` — invalidates previous OTPs before generating; passes type='email_verification'
- `modules/Channel/App/Http/Controllers/V1/ChannelController.php` — forgetPassword fires PasswordResetRequested; validateOtp uses getLatestUnverified+markAsVerified; resetPassword uses password_reset type; added resendOtp(), logout(), refreshToken()
- `modules/Channel/routes/api-v1.php` — added resend-otp (public), auth/logout + auth/refresh (protected)
- `modules/Channel/App/Providers/EventServiceProvider.php` — registered PasswordResetRequested => SendPasswordResetOtpListener
- `modules/Channel/resources/lang/en/app.php` — added mail.reset_password_*, auth.logout_success, auth.token_refreshed, auth.token_expired, otp.resent
- `modules/Channel/resources/lang/ar/app.php` — same keys in Arabic

### Migrations run
- `2026_06_08_000001_add_type_to_otps_table` ✅

### Swagger
- ChannelController updated with @OA\\ annotations for all new endpoints (resendOtp, logout, refreshToken)
- `php artisan l5-swagger:generate` — SUCCESS
