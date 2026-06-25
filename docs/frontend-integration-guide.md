# Teachify API — Frontend Integration Guide

> **Version:** 1.0.0 · **Last updated:** 2026-06-21
> This guide covers every module implemented through Phase 9. Update it alongside each new backend phase.

---

## Quick-start checklist

- [ ] Read **[Core Concepts](#core-concepts)** — slug routing and JWT flow are non-negotiable foundations
- [ ] Implement the **[Auth flow](#auth--session-management)** first (register → verify email → login → store token)
- [ ] Confirm the channel slug with the backend team — every authenticated call uses it
- [ ] Check the **[Error envelope](#error-envelope)** shape — all errors follow the same structure
- [ ] Review **[Permission gates](#permission-gates)** — your UI must hide actions the current role cannot perform

---

## Base URL

```
https://{your-domain}/api/v1
```

All authenticated endpoints are prefixed with `{channel_slug}`:

```
https://{your-domain}/api/v1/{channel_slug}/students
```

Public (unauthenticated) endpoints use `/api/v1/auth/...`.

---

## Core Concepts

### Channel slug

Every Teachify deployment is a **channel** — an isolated tenant. The slug is a URL-safe identifier auto-generated at registration (e.g. `smart-academy-1`). Store it alongside the JWT after login. All protected API calls require it as a path segment.

```
GET /api/v1/smart-academy-1/students
     ^^^^^^^^^^^^^^^^^^^^^^^^^^
     every authenticated call needs this
```

### JWT authentication

Teachify uses **JWT** (not sessions or Sanctum). Tokens are stateless and must be sent as a Bearer header on every authenticated request.

```http
Authorization: Bearer eyJ0eXAiOiJKV1Q...
```

Token lifecycle:
- **Login** → returns `access_token` + `expires_in` (seconds)
- **Refresh** → `POST /{channel_slug}/auth/refresh` — call this before the token expires
- **Logout** → `POST /{channel_slug}/auth/logout` — invalidates the token server-side

Store the token in `localStorage` or `sessionStorage`. Never store it in a cookie without `HttpOnly`.

### Error envelope

Every response — success and failure — follows this structure:

```json
{
  "success": true,
  "message": "Students retrieved successfully.",
  "data": { ... }
}
```

Error responses:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

| HTTP status | Meaning |
|---|---|
| `200` | Success |
| `201` | Created |
| `401` | Unauthenticated — token missing or expired |
| `403` | Forbidden — authenticated but permission denied |
| `404` | Resource not found |
| `409` | Conflict (e.g. QR scan after manual absence) |
| `422` | Validation error — `errors` object present |
| `500` | Server error |

### Permission gates

The authenticated user carries a `permissions` array on their role. The API enforces permissions per action. Your UI should reflect this — hide or disable buttons the current user cannot act on.

```json
{
  "user": {
    "role": {
      "name": "teacher",
      "permissions": ["students.view", "attendance.manage", "exams.view", "exams.create"]
    }
  }
}
```

**Owner role** has `permissions: "all"` (a string, not an array) — always has full access.

Permission catalog:

| Permission | Owner | Teacher | Assistant | Viewer |
|---|---|---|---|---|
| `users.*` / `roles.*` | ✅ | ❌ | ❌ | ❌ |
| `courses.view` | ✅ | ✅ | ✅ | ✅ |
| `groups.view` | ✅ | ✅ | ✅ | ✅ |
| `sessions.view` | ✅ | ✅ | ✅ | ✅ |
| `sessions.create/update` | ✅ | ✅ | ❌ | ❌ |
| `subjects.view` | ✅ | ✅ | ✅ | ✅ |
| `students.view` | ✅ | ✅ | ✅ | ✅ |
| `students.create` | ✅ | ✅ | ✅ | ❌ |
| `attendance.view` | ✅ | ✅ | ✅ | ✅ |
| `attendance.manage` | ✅ | ✅ | ✅ | ❌ |
| `exams.view` | ✅ | ✅ | ✅ | ✅ |
| `exams.create/update/delete` | ✅ | ✅ | ❌ | ❌ |
| `payments.*` | ✅ | ✅ | ❌ | ❌ |
| `reports.view` | ✅ | ✅ | ❌ | ✅ |

---

## Module Reference

---

### Auth & Session Management

> Public endpoints — no `channel_slug`, no `Authorization` header.

#### Register

```http
POST /api/v1/auth/register
```

```json
{
  "channel_name": "Smart Academy",
  "channel_type": "center",
  "name": "Ahmed Hassan",
  "email": "ahmed@smartacademy.eg",
  "phone": "01012345678",
  "gender": "male",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

Returns `201` with `{ user, channel, token }`. The channel slug is in `channel.slug` — persist it.

After registration, the email is **unverified**. The user must verify before full access.

#### Verify email (OTP)

```http
POST /api/v1/auth/verify-email
```

```json
{ "email": "ahmed@smartacademy.eg", "otp": "847291" }
```

#### Resend OTP

```http
POST /api/v1/auth/resend-otp
```

```json
{ "email": "ahmed@smartacademy.eg" }
```

#### Login

```http
POST /api/v1/auth/login
```

```json
{ "email": "ahmed@smartacademy.eg", "password": "secret123" }
```

OR by phone:

```json
{ "phone": "01012345678", "password": "secret123" }
```

Returns `{ access_token, token_type: "bearer", expires_in, user, channel }`.

#### Password reset

```http
POST /api/v1/auth/forget-password
{ "email": "ahmed@smartacademy.eg" }

POST /api/v1/auth/reset-password
{ "email": "...", "otp": "...", "password": "...", "password_confirmation": "..." }
```

#### Get current user

```http
GET /api/v1/{channel_slug}/auth/me
Authorization: Bearer {token}
```

#### Logout / Refresh

```http
POST /api/v1/{channel_slug}/auth/logout
POST /api/v1/{channel_slug}/auth/refresh
```

---

### Users & Roles (RBAC)

> Requires `users.*` or `roles.*` — **owner only** by default.

#### List users

```http
GET /api/v1/{channel_slug}/users
```

#### Create staff user

```http
POST /api/v1/{channel_slug}/users
```

```json
{
  "name": "Sara Ali",
  "email": "sara@smartacademy.eg",
  "phone": "01098765432",
  "gender": "female",
  "password": "secret123",
  "password_confirmation": "secret123",
  "role_id": 2
}
```

#### Roles

```http
GET    /api/v1/{channel_slug}/roles
POST   /api/v1/{channel_slug}/roles      { "name": "...", "permissions": ["students.view", "attendance.manage"] }
PUT    /api/v1/{channel_slug}/roles/{id}
DELETE /api/v1/{channel_slug}/roles/{id}
```

`permissions` is an array of permission strings. Owner passes `"all"`.

---

### Academic — Courses, Groups, Sessions

#### Courses

> Requires `courses.view` to read.

```http
GET    /api/v1/{channel_slug}/courses
GET    /api/v1/{channel_slug}/courses/{id}
POST   /api/v1/{channel_slug}/courses          { "name": "...", "type": "online|offline|hybrid" }
POST   /api/v1/{channel_slug}/courses/{id}     (multipart/form-data for cover_image upload)
DELETE /api/v1/{channel_slug}/courses/{id}
```

`type` enum: `online`, `offline`, `hybrid`
`status` enum: `draft`, `active`, `archived`

#### Groups

```http
GET    /api/v1/{channel_slug}/groups
GET    /api/v1/{channel_slug}/groups/{id}
POST   /api/v1/{channel_slug}/groups
PUT    /api/v1/{channel_slug}/groups/{id}
DELETE /api/v1/{channel_slug}/groups/{id}
```

`payment_model` enum: `monthly`, `per_course`, `per_session`
`status` enum: `active`, `full`, `archived`

#### Group users (teachers/assistants)

```http
GET    /api/v1/{channel_slug}/groups/{groupId}/users
POST   /api/v1/{channel_slug}/groups/{groupId}/users     { "user_id": 3, "role": "teacher" }
PUT    /api/v1/{channel_slug}/groups/{groupId}/users/{userId}
DELETE /api/v1/{channel_slug}/groups/{groupId}/users/{userId}
```

#### Sessions

> Sessions are individual dated class instances, not recurring templates.

```http
GET    /api/v1/{channel_slug}/groups/{group}/sessions
       ?status=scheduled|live|completed|cancelled
       &from=2026-07-01&to=2026-07-31

GET    /api/v1/{channel_slug}/groups/{group}/sessions/{session}

POST   /api/v1/{channel_slug}/groups/{group}/sessions
PATCH  /api/v1/{channel_slug}/groups/{group}/sessions/{session}
DELETE /api/v1/{channel_slug}/groups/{group}/sessions/{session}
```

**One-off session:**
```json
{
  "scheduled_at": "2026-07-15T10:00:00",
  "duration_minutes": 90,
  "type": "offline",
  "location": "Room 3"
}
```

**Recurring session (generates 90-day instances):**
```json
{
  "recurring_rule": {
    "day": "monday",
    "start_time": "10:00",
    "end_time": "11:30"
  }
}
```

`status` enum: `scheduled`, `live`, `completed`, `cancelled`

#### Generate QR code for a session

> Requires `sessions.create`.

```http
POST /api/v1/{channel_slug}/groups/{group}/sessions/{session}/qr
```

Returns `{ qr_token, qr_expires_at, session_id }`. The token is HMAC-signed and expires at session end + 30 min. Regenerating invalidates the previous token.

---

### Subjects

```http
GET    /api/v1/{channel_slug}/subjects
       ?is_active=1&is_general=0&per_page=20

GET    /api/v1/{channel_slug}/subjects/{id}

POST   /api/v1/{channel_slug}/subjects
{
  "code": "MATH-101",
  "credits": 3,
  "translations": {
    "en": { "name": "Algebra" },
    "ar": { "name": "الجبر" }
  }
}

PUT    /api/v1/{channel_slug}/subjects/{id}
DELETE /api/v1/{channel_slug}/subjects/{id}
```

---

### Students

```http
GET    /api/v1/{channel_slug}/students
       ?search=ahmed&group_id=5&status=active&gender=male&per_page=20

GET    /api/v1/{channel_slug}/students/{id}

POST   /api/v1/{channel_slug}/students
{
  "name": "Ahmed Khaled",
  "gender": "male",
  "email": "ahmed@example.com",
  "phone": "01011223344",
  "birth_date": "2010-05-15"
}

PUT    /api/v1/{channel_slug}/students/{id}
DELETE /api/v1/{channel_slug}/students/{id}
```

`status` enum: `active`, `inactive`, `suspended`
`gender` enum: `male`, `female`

#### Guardians (nested under students)

```http
GET    /api/v1/{channel_slug}/students/{student}/guardians
GET    /api/v1/{channel_slug}/students/{student}/guardians/{id}

POST   /api/v1/{channel_slug}/students/{student}/guardians
{
  "name": "Khaled Hassan",
  "phone": "01055667788",
  "relationship": "father",
  "is_primary": true
}

PUT    /api/v1/{channel_slug}/students/{student}/guardians/{id}
DELETE /api/v1/{channel_slug}/students/{student}/guardians/{id}
```

`relationship` enum: `father`, `mother`, `guardian`, `other`

Setting `is_primary: true` clears the primary flag on other guardians for that student.

---

### Enrollments

```http
GET    /api/v1/{channel_slug}/student-enrollments
       ?student_id=12&group_id=5&status=active

GET    /api/v1/{channel_slug}/student-enrollments/{id}

POST   /api/v1/{channel_slug}/student-enrollments
{
  "student_id": 12,
  "group_id": 5,
  "enrollment_type": "monthly",
  "start_date": "2026-07-01",
  "agreed_monthly_fee": 500
}

PUT    /api/v1/{channel_slug}/student-enrollments/{id}
DELETE /api/v1/{channel_slug}/student-enrollments/{id}

GET /api/v1/{channel_slug}/students/{studentId}/enrollments
GET /api/v1/{channel_slug}/groups/{groupId}/enrollments
```

`enrollment_type` enum: `monthly`, `per_course`, `per_session`

**Important:** Creating an enrollment automatically generates a **prorated first-month invoice**. If `start_date` is mid-month, the amount is `fee × (daysRemaining / daysInMonth)`.

---

### Attendance

#### Manual attendance

```http
POST /api/v1/{channel_slug}/attendances
{
  "student_id": 12,
  "group_id": 5,
  "date": "2026-07-15",
  "status": "absent",
  "session_id": 23
}

PUT    /api/v1/{channel_slug}/attendances/{id}
DELETE /api/v1/{channel_slug}/attendances/{id}
```

`status` enum: `present`, `absent`, `late`, `excused`

#### Bulk attendance (for a session)

```http
POST /api/v1/{channel_slug}/attendances/bulk
{
  "session_id": 23,
  "attendances": [
    { "student_id": 12, "status": "present" },
    { "student_id": 15, "status": "absent" }
  ]
}
```

#### QR scan (student self-check-in)

```http
POST /api/v1/{channel_slug}/attendances/qr-scan
{
  "token": "eyJzZXNzaW9uX2lkIjo...",
  "student_id": 12
}
```

Behavior:
- Within 15 min of `scheduled_at` → status: `present`
- After 15 min → status: `late`
- If student was manually marked `absent` → **409 Conflict** (blocked, cannot override)
- Expired or tampered token → **400 Bad Request**
- Already checked in → **422**

#### Live attendance (polling endpoint)

```http
GET /api/v1/{channel_slug}/sessions/{session}/attendance
```

Returns live counts `{ total, present, late, absent, excused }` plus the full attendance list. Poll this every 5–10 seconds to build a live dashboard.

#### Statistics

```http
GET /api/v1/{channel_slug}/attendances/statistics/student/{studentId}
    ?start_date=2026-07-01&end_date=2026-07-31

GET /api/v1/{channel_slug}/attendances/statistics/group/{groupId}
    ?date=2026-07-15
```

---

### Payments & Invoices

> Requires `payments.*`.

#### Invoices

```http
GET /api/v1/{channel_slug}/invoices/student/{studentId}
GET /api/v1/{channel_slug}/invoices/overdue
GET /api/v1/{channel_slug}/invoices/pending

POST /api/v1/{channel_slug}/invoices
{
  "student_id": 12,
  "group_id": 5,
  "total_amount": 500,
  "due_date": "2026-07-31",
  "type": "monthly",
  "issue_date": "2026-07-01"
}
```

`type` enum: `monthly`, `session`, `enrollment_fee`, `ad_hoc`

For `type: "ad_hoc"`, `reason` is **required**:
```json
{ ..., "type": "ad_hoc", "reason": "Textbook fee for Q3" }
```

#### Invoices with installments

```http
POST /api/v1/{channel_slug}/invoices/with-installments
{
  "student_id": 12,
  "total_amount": 1500,
  "due_date": "2026-09-30",
  "installments": [
    { "amount": 500, "due_date": "2026-07-31" },
    { "amount": 500, "due_date": "2026-08-31" },
    { "amount": 500, "due_date": "2026-09-30" }
  ]
}
```

#### Payments

```http
POST /api/v1/{channel_slug}/payments
{
  "student_id": 12,
  "invoice_id": 34,
  "amount": 500,
  "payment_method": "cash",
  "payment_date": "2026-07-15"
}

POST /api/v1/{channel_slug}/payments/{id}/complete
POST /api/v1/{channel_slug}/payments/{id}/refund

GET /api/v1/{channel_slug}/payments/student/{studentId}
GET /api/v1/{channel_slug}/payments/group/{groupId}
GET /api/v1/{channel_slug}/payments/statistics
GET /api/v1/{channel_slug}/payments/student/{studentId}/summary
```

`payment_method` enum: `cash`, `bank_transfer`, `vodafone_cash`, `orange_money`, `etisalat_cash`, `easy_pay`, `credit_card`, `debit_card`, `online`, `other`

**Overpayment is rejected.** Amount must not exceed `invoice.remaining_amount`.

---

### Exams

#### Exam CRUD

```http
GET    /api/v1/{channel_slug}/exams
       ?group_id=5&status=published

GET    /api/v1/{channel_slug}/exams/{id}

POST   /api/v1/{channel_slug}/exams
{
  "group_id": 5,
  "title": "Midterm Exam — Algebra",
  "description": "Chapters 1–4",
  "total_marks": 100,
  "pass_marks": 50,
  "duration_minutes": 90,
  "allow_retake": false,
  "max_attempts": 1,
  "starts_at": "2026-07-20T09:00:00",
  "ends_at": "2026-07-20T11:30:00"
}

PUT    /api/v1/{channel_slug}/exams/{id}
DELETE /api/v1/{channel_slug}/exams/{id}
```

`status` enum: `draft`, `published`, `closed`

**Lifecycle:**
```http
POST /api/v1/{channel_slug}/exams/{id}/publish   → draft → published
POST /api/v1/{channel_slug}/exams/{id}/close     → published → closed
```

Cannot publish an exam with zero questions. Cannot edit a published exam that already has submissions.

#### Questions

```http
GET    /api/v1/{channel_slug}/exams/{examId}/questions
GET    /api/v1/{channel_slug}/exams/{examId}/questions/{id}

POST   /api/v1/{channel_slug}/exams/{examId}/questions
{
  "question": "What is the value of x when 2x + 4 = 10?",
  "type": "mcq",
  "marks": 5,
  "order": 1,
  "options": [
    { "text": "2", "is_correct": false, "order": 1 },
    { "text": "3", "is_correct": true,  "order": 2 },
    { "text": "4", "is_correct": false, "order": 3 },
    { "text": "5", "is_correct": false, "order": 4 }
  ]
}

PUT    /api/v1/{channel_slug}/exams/{examId}/questions/{id}
DELETE /api/v1/{channel_slug}/exams/{examId}/questions/{id}
```

`type` enum: `mcq`, `true_false`, `short_answer`, `essay`

MCQ/true_false **must** have exactly one option with `is_correct: true`. `is_correct` is hidden from students during the attempt — only returned on result routes.

#### Student submission flow

```http
POST /api/v1/{channel_slug}/exams/{examId}/start
{ "student_id": 12 }
→ returns { submission_id: 45, status: "in_progress" }
```

```http
POST /api/v1/{channel_slug}/exams/{examId}/submissions/{submissionId}/submit
{
  "student_id": 12,
  "answers": [
    { "question_id": 7, "selected_option_id": 22 },
    { "question_id": 8, "selected_option_id": null, "answer_text": "The commutative property states..." }
  ]
}
```

Auto-grading on submit:
- **MCQ / true_false** → scored immediately
- **short_answer / essay** → `status: "submitted"`, waits for teacher

#### Teacher grading (essays)

```http
POST /api/v1/{channel_slug}/exams/{examId}/submissions/{submissionId}/grade
{
  "grades": [
    { "answer_id": 88, "marks_obtained": 8 }
  ],
  "teacher_notes": "Good reasoning, but lacks formal notation."
}
```

After grading all essays, `status` becomes `graded` and `is_pass` is set.

#### Results

```http
GET  /api/v1/{channel_slug}/exams/{examId}/results
GET  /api/v1/{channel_slug}/exams/{examId}/submissions
GET  /api/v1/{channel_slug}/exams/{examId}/submissions/{submissionId}
```

---

## Pagination

All list endpoints return paginated results:

```json
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 15, "total": 87, "last_page": 6 }
}
```

Control page size with `?per_page=N`. Default is `15`.

---

## Enum reference

| Field | Values |
|---|---|
| `gender` | `male`, `female` |
| `channel_type` | `teacher`, `center` |
| `course.type` | `online`, `offline`, `hybrid` |
| `course.status` | `draft`, `active`, `archived` |
| `group.payment_model` | `monthly`, `per_course`, `per_session` |
| `group.status` | `active`, `full`, `archived` |
| `session.status` | `scheduled`, `live`, `completed`, `cancelled` |
| `enrollment_type` | `monthly`, `per_course`, `per_session` |
| `attendance.status` | `present`, `absent`, `late`, `excused` |
| `invoice.type` | `monthly`, `session`, `enrollment_fee`, `ad_hoc` |
| `payment_method` | `cash`, `bank_transfer`, `vodafone_cash`, `orange_money`, `etisalat_cash`, `easy_pay`, `credit_card`, `debit_card`, `online`, `other` |
| `exam.status` | `draft`, `published`, `closed` |
| `exam_question.type` | `mcq`, `true_false`, `short_answer`, `essay` |
| `exam_submission.status` | `in_progress`, `submitted`, `graded` |
| `guardian.relationship` | `father`, `mother`, `guardian`, `other` |

---

## Behavioral rules (business logic)

These are not validation errors — they are server-enforced business rules your UI must account for.

| Rule | HTTP status | When it triggers |
|---|---|---|
| QR scan after manual absent | `409` | Student was marked absent before scanning |
| QR token expired | `400` | Scanned more than 30 min after session end |
| Overpayment rejected | `422` | Payment amount exceeds `invoice.remaining_amount` |
| Mid-month proration | auto | First invoice auto-created on enrollment at prorated amount |
| Max exam attempts reached | `422` | Student tries to start beyond `max_attempts` |
| Cannot publish exam with no questions | `422` | `POST /exams/{id}/publish` with 0 questions |
| Cannot delete exam with submissions | `422` | Exam has at least one submission |
| Ad-hoc invoice requires reason | `422` | `type: "ad_hoc"` without `reason` field |
| Attendance late threshold | auto | QR scan > 15 min after `scheduled_at` → status `late` |

---

## OpenAPI / Swagger

The full machine-readable spec is available at:

```
GET /api/documentation
```

Or open the Swagger UI at `/api/documentation` in a browser when the backend is running.
