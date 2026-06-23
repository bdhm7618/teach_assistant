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

---

## Phase 2 — RBAC Completion — 2026-06-09

### Created
- `modules/Core/App/Http/Middleware/CheckPermission.php` — validates `user->hasAllPermissions()`; owner (permissions="all") always passes; comma-separated multi-permission support

### Modified
- `bootstrap/app.php` — registered `check.permission` middleware alias
- `modules/Channel/App/Http/Controllers/V1/ChannelController.php` — register() now looks up owner system role and assigns it to the new user via `role_id`
- `modules/Channel/Database/Seeders/RoleSeeder.php` — rationalized permission catalog: `courses.view`, `groups.view`, `sessions.*`, `students.*`, `attendance.*`, `payments.*`, `reports.view`, `users.*`, `roles.*`
- `modules/Channel/routes/api-v1.php` — expanded `apiResource` into explicit routes with per-action `check.permission` middleware for `users` and `roles`
- `modules/Channel/App/Http/Controllers/V1/UserController.php` — added full @OA\\ Swagger annotations for all 5 endpoints
- `modules/Channel/App/Http/Controllers/V1/RoleController.php` — added full @OA\\ Swagger annotations for all 5 endpoints

### Seeder run
- `RoleSeeder` — updated permissions for teacher, assistant, viewer system roles ✅

### Permission catalog
| Permission | owner | teacher | assistant | viewer |
|---|---|---|---|---|
| `users.*` / `roles.*` | ✅ | ❌ | ❌ | ❌ |
| `courses.view` | ✅ | ✅ | ✅ | ✅ |
| `groups.view` | ✅ | ✅ | ✅ | ✅ |
| `sessions.view` | ✅ | ✅ | ✅ | ✅ |
| `sessions.create/update` | ✅ | ✅ | ❌ | ❌ |
| `students.view` | ✅ | ✅ | ✅ | ✅ |
| `students.create` | ✅ | ✅ | ✅ | ❌ |
| `attendance.view` | ✅ | ✅ | ✅ | ✅ |
| `attendance.manage` | ✅ | ✅ | ✅ | ❌ |
| `reports.view` | ✅ | ✅ | ❌ | ✅ |

### Swagger
- UserController and RoleController now have full @OA\\ annotations
- `php artisan l5-swagger:generate` — SUCCESS

---

## Phase 3 — Subject Management — 2026-06-09

### Modified
- `modules/Academic/App/Http/Controllers/V1/SubjectController.php` — added full @OA\\ annotations for all 5 endpoints; refactored `createTranslations/updateTranslations` into single `saveTranslations($upsert)` helper
- `modules/Academic/routes/api-v1.php` — replaced `Route::apiResource('subjects', ...)` with explicit per-action routes gated by `check.permission:subjects.view/create/update/delete`

### No migrations needed
- subjects + subject_translations tables already existed and are in correct shape

### Swagger
- SubjectController now has full @OA\\ annotations (5 endpoints) with filter params documented
- `php artisan l5-swagger:generate` — SUCCESS

---

## Phase 5 — Parent / Guardian — 2026-06-09

### Created
- `modules/Student/Database/Migrations/2026_06_09_000001_create_guardians_table.php` — guardians table with student_id FK, relationship enum, is_primary flag
- `modules/Student/App/Models/Guardian.php` — HasChannelScope, belongsTo Student
- `modules/Student/App/Http/Controllers/V1/GuardianController.php` — full CRUD nested under students/{student}/guardians; is_primary enforcement (clears other primary on set)
- `modules/Student/App/Http/Requests/V1/GuardianRequest.php`
- `modules/Student/App/Http/Resources/V1/GuardianResource.php`

### Modified
- `modules/Student/App/Models/Student.php` — added guardians() and primaryGuardian() relationships
- `modules/Student/App/Http/Controllers/V1/StudentController.php` — removed invalid withCount(['attendances','payments']); fixed duplicate-count; added full @OA\\ Swagger annotations for all 5 endpoints; loads guardians in show()
- `modules/Student/App/Http/Resources/V1/StudentResource.php` — added guardians and primary_guardian whenLoaded; removed stale attendances_count/payments_count
- `modules/Student/routes/api-v1.php` — moved metadata route BEFORE resource routes (prevents 'metadata' matching {id}); replaced apiResource with explicit permission-gated routes; added guardian nested routes
- `modules/Student/resources/lang/en/app.php` — added guardian.* keys
- `modules/Student/resources/lang/ar/app.php` — same in Arabic

### Migrations run
- `2026_06_09_000001_create_guardians_table` ✅

### Swagger
- StudentController + GuardianController fully annotated
- `php artisan l5-swagger:generate` — SUCCESS

---

## Schema Audit + Fixes — 2026-06-10

### Issues identified and resolved

| # | Table | Issue | Fix |
|---|-------|-------|-----|
| 1 | `admins` | `geneder` column typo | Renamed to `gender` |
| 2 | `students` | `code`, `email`, `phone` had global UNIQUE — breaks multi-tenancy | Dropped global uniques; added composite UNIQUE per channel |
| 3 | `group_students` | No `channel_id`, no UNIQUE constraint → duplicate enrollments possible | Added `channel_id` FK + `UNIQUE(group_id, student_id)` |
| 4 | `student_enrollments` | `enrollment_type` values (`course`, `session_package`) didn't match `groups.payment_model` (`per_course`, `per_session`) | Migrated data + altered enum to `monthly`, `per_course`, `per_session` |

### Created
- `modules/Admin/database/migrations/2026_06_10_000001_fix_admins_geneder_typo.php`
- `modules/Student/database/migrations/2026_06_10_000002_fix_students_unique_constraints.php`
- `modules/Student/database/migrations/2026_06_10_000003_fix_group_students_add_channel_and_unique.php`
- `modules/Academic/database/migrations/2026_06_10_000004_fix_student_enrollments_enum.php`

### Migrations run
- All 4 fix migrations ✅

### No renames needed
- `group_sessions` name is intentional (avoids conflict with Laravel's `sessions` table)
- `session_times` name is accurate and descriptive enough
- All other table names match the BRD plan

---

## Phase 8 — Attendance QR + Realtime — 2026-06-17

### Created
- `modules/Academic/database/migrations/2026_06_17_000002_add_qr_to_group_sessions.php` — adds `qr_token` (unique) + `qr_expires_at` to `group_sessions`
- `modules/Attendance/app/Events/AttendanceRecorded.php` — fires after every attendance record; ready for Reverb/Pusher broadcasting

### Modified
- `modules/Academic/app/Models/Session.php` — added `qr_token`/`qr_expires_at` to fillable; added `refreshQrToken()` (HMAC-signed token, expiry = scheduled_at + duration + 30 min) and `parseQrToken()` static; added `attendances()` relationship
- `modules/Academic/app/Http/Controllers/V1/SessionController.php` — added `generateQr()` with `@OA\` annotation
- `modules/Academic/routes/api-v1.php` — added `POST groups/{group}/sessions/{session}/qr`; added `check.permission` gates to session write routes
- `modules/Attendance/app/Models/Attendance.php` — added `session_id` to fillable; added `session()` BelongsTo relationship
- `modules/Attendance/app/Http/Requests/V1/AttendanceRequest.php` — added `session_id` field; duplicate check now scoped to `session_id` when provided
- `modules/Attendance/app/Http/Resources/V1/AttendanceResource.php` — added `session_id` + `session` (whenLoaded)
- `modules/Attendance/app/Repositories/AttendanceRepository.php` — `exists()` accepts optional `session_id`; added `getBySession()` method
- `modules/Attendance/app/Http/Controllers/V1/AttendanceController.php` — full rewrite: added `qrScan()` (token verify + 15-min late rule + block-if-absent), `sessionLive()` (polling endpoint), updated `bulkStore()` with session_id; full `@OA\` Swagger on all 8 endpoints
- `modules/Attendance/routes/api-v1.php` — `check.permission:attendance.view/manage` gates; added `qr-scan` + `sessions/{session}/attendance` routes
- `modules/Attendance/resources/lang/en/app.php` + `ar/app.php` — added `qr.*` and `live_retrieved` keys

### Business rules enforced
| Rule | Where |
|------|-------|
| QR scan > 15 min after `scheduled_at` → `late` | `AttendanceController::qrScan()` |
| QR scan when student manually `absent` → BLOCK (409) | `AttendanceController::qrScan()` |
| QR token signed with HMAC-SHA256 (app key) | `Session::refreshQrToken()` / `parseQrToken()` |
| QR expires at session end + 30 min grace | `Session::refreshQrToken()` |

### Realtime strategy
- `AttendanceRecorded` event fired on every record creation — wire to Reverb/Pusher when ready
- Polling endpoint `GET /sessions/{session}/attendance` returns live attendance counts + records
- No external WebSocket dependency for MVP

### Pending migration (run when WAMP MySQL is started)
- `2026_06_17_000002_add_qr_to_group_sessions` — adds `qr_token` + `qr_expires_at` to `group_sessions`

---

## Phase 9 — Exam Module — 2026-06-21

### Created
**Module:** `modules/Exam/` (new nwidart module)

**Providers:**
- `modules/Exam/app/Providers/ExamServiceProvider.php`
- `modules/Exam/app/Providers/EventServiceProvider.php`
- `modules/Exam/app/Providers/RouteServiceProvider.php`

**Migrations (5, pending WAMP start):**
- `2026_06_21_000001_create_exams_table` — `allow_retake`, `max_attempts`, `status`, `starts_at`, `ends_at`
- `2026_06_21_000002_create_exam_questions_table` — `type` (mcq|true_false|short_answer|essay), `marks`, `order`
- `2026_06_21_000003_create_exam_options_table` — MCQ/T-F answer choices with `is_correct`
- `2026_06_21_000004_create_exam_submissions_table` — student attempts with auto-grade result fields
- `2026_06_21_000005_create_exam_answers_table` — per-question student responses

**Models:**
- `modules/Exam/app/Models/Exam.php` — HasChannelScope + SoftDeletes; `canBeAttemptedBy()` enforces max_attempts + window
- `modules/Exam/app/Models/ExamQuestion.php` — `isObjective()` helper
- `modules/Exam/app/Models/ExamOption.php`
- `modules/Exam/app/Models/ExamSubmission.php` — `autoGrade()` auto-scores MCQ/T-F; recalculates totals; sets `is_pass`
- `modules/Exam/app/Models/ExamAnswer.php`

**Repository:**
- `modules/Exam/app/Repositories/ExamRepository.php`

**Controllers (3, full @OA\ Swagger):**
- `modules/Exam/app/Http/Controllers/V1/ExamController.php` — 7 endpoints: CRUD + publish + close + results
- `modules/Exam/app/Http/Controllers/V1/ExamQuestionController.php` — 5 endpoints: CRUD nested under exam
- `modules/Exam/app/Http/Controllers/V1/ExamSubmissionController.php` — 5 endpoints: list + start + submit + show + grade

**Requests (4):**
- `ExamRequest`, `ExamQuestionRequest`, `ExamSubmitRequest`, `ExamGradeRequest`

**Resources (5):**
- `ExamResource`, `ExamQuestionResource`, `ExamOptionResource`, `ExamSubmissionResource`, `ExamAnswerResource`

**Routes:** `modules/Exam/routes/api-v1.php`
**Lang:** `modules/Exam/resources/lang/en/app.php` + `ar/app.php`

### Modified
- `modules_statuses.json` — added `"Exam": true`
- `modules/Channel/database/seeders/RoleSeeder.php` — added `exams.*` permissions to teacher; `exams.view` to assistant + viewer

### Business rules enforced
| Rule | Where |
|------|-------|
| Locked: `allow_retake` + `max_attempts` per exam | `Exam::canBeAttemptedBy()` |
| MCQ/T-F auto-graded on submit | `ExamSubmission::autoGrade()` |
| Essay/short-answer waits for teacher grade | `autoGrade()` sets status `submitted` not `graded` |
| Cannot publish exam with 0 questions | `ExamController::publish()` |
| Cannot edit published exam with submissions | `ExamController::update()` |
| Cannot delete exam with submissions | `ExamController::destroy()` |
| Cannot modify closed exam questions | `ExamQuestionController` |
| `is_correct` hidden from students during attempt | `ExamOptionResource` (route-based gate) |

### Pending migrations (run when WAMP MySQL is started)
- All 5 exam migrations
- `2026_06_17_000002_add_qr_to_group_sessions` (P8, still pending)
