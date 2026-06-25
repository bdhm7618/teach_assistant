# Teachify — Product Overview & User Stories

> For frontend developers. Read this before building any screen.  
> Last updated: 2026-06-23 (reviewed against the codebase through Phase 9)

> **How to read this doc:** This is the *why* — business intent, flows, and the rules the UI must honor. For exact request/response shapes, status codes, and enums, see the **[Frontend Integration Guide](./frontend-integration-guide.md)**. ⚠️ This 2026-06-23 review reconciled both docs against the actual backend; where any doc disagrees with the code, **the code wins** — the `⚠️` callouts below flag the specific places the older text was wrong (notably: payments are Owner-only, no `partially_paid` invoice status, expired QR returns 400, and only `monthly` enrollments auto-invoice). The integration guide still has a couple of these stale (its permission catalog over-grants `payments.*` to Teacher) — trust this doc's Permissions table and tell the backend team to align both.

> ⚠️ **Build scope today (Phases 1–9):** Owner/staff-facing web app only. The **student app, guardian/parent portal, assignments, notifications, and live video are NOT built yet** (Phases 10–15). Stories below that involve a student acting on their own device (US-14, US-23) describe the *intended* flow, but there is no student login or student-facing surface to build against yet — do not start those screens until the Student Portal phase ships. They're included so the staff-side screens (QR display, exam authoring/grading) are designed to fit them later.

---

## What is Teachify?

Teachify is a **multi-tenant education management SaaS** designed for Egyptian private tutoring centers and independent teachers. Each subscribing center or teacher gets their own isolated **channel** — their private workspace in the platform.

The platform replaces the manual, WhatsApp-group-based chaos that most small tutoring centers run today:

| Before Teachify | After Teachify |
|---|---|
| Attendance on paper or voice messages | QR scan on phone, auto-present/late logic |
| Payments tracked in Excel or a notebook | Invoices per student, partial payments, installments |
| Exam papers handed out physically | Digital exams with auto-grading for MCQ |
| Parents ask teachers "how is my son doing?" | Guardian portal with live data (future phase) |
| No visibility across multiple teachers | Owner sees everything; staff sees only what their role allows |

---

## Who uses it?

### The four roles

| Role | Who they are | What they do (per the default seeded permissions) |
|---|---|---|
| **Owner** | The center owner or solo teacher who paid for the subscription | Full access to everything — users, roles, billing, all data (`permissions: "all"`). **The only role that can touch payments/invoices by default.** |
| **Teacher** | A subject teacher at the center | Manages sessions, marks attendance, full exam authoring/grading, views students/courses/reports. **Cannot** register new students or access payments by default. |
| **Assistant** | An admin or receptionist | Registers students and manages attendance. Read-only on exams; **no** payments access. |
| **Viewer** | A silent observer (e.g. academic supervisor) | Read-only across the board plus reports. Changes nothing. |

> Roles are **customizable** — an Owner can create new roles with any mix of permissions (US-05). The table above is just the four seeded defaults; always render the UI from the live `permissions` value, never from a hard-coded role name.

---

## Core concepts the UI must reflect

### 1. Channel = the tenant

Every URL and data object is scoped to a channel. The channel slug is part of every API URL. The UI should always know which channel the user belongs to and never mix data between channels.

### 2. Hierarchy: Course → Group → Session

```
Course (e.g. "Grade 10 Math")
  └── Group (e.g. "Mon/Wed 4 PM — 12 students")
        └── Session (e.g. "Monday July 14 — 4:00 PM")
```

- A **Course** is the subject offering (online/offline/hybrid, linked to a Subject).
- A **Group** is a scheduled cohort: it has a `payment_model` (monthly, per-course, per-session) and a roster of enrolled students.
- A **Session** is one dated class instance. Sessions can be one-off or generated from a recurring rule (weekly schedule).

### 3. Monthly enrollment triggers money

When a student is enrolled into a group on a **`monthly`** plan (and an `agreed_monthly_fee` is set), the system **automatically creates their first invoice** — prorated if they join mid-month. The UI does not need to create this invoice manually; it just needs to surface it.

⚠️ **Only `monthly` enrollments auto-invoice today.** `per_course` and `per_session` enrollments do **not** generate an invoice on creation — the staff member raises charges for those manually (US-18 ad-hoc, or future per-session billing). Don't promise "first invoice created" in the UI for those plan types.

### 4. Permissions gate the UI

Every action button, tab, and menu item should check the user's `permissions` before rendering. The owner has `permissions: "all"` (a **string**, not an array) — your check must handle both shapes: `perms === "all" || perms.includes("foo.bar")`. All other roles carry an array of permission strings. Hide controls preemptively rather than waiting for a 403.

**The real default permission strings** (from the role seeder — these are the canonical names to check against):

| Role | Permissions |
|---|---|
| **Owner** | `"all"` (string) |
| **Teacher** | `courses.view`, `groups.view`, `sessions.view`, `sessions.create`, `sessions.update`, `subjects.view`, `students.view`, `attendance.view`, `attendance.manage`, `exams.view`, `exams.create`, `exams.update`, `exams.delete`, `reports.view` |
| **Assistant** | `courses.view`, `groups.view`, `sessions.view`, `subjects.view`, `students.view`, `students.create`, `attendance.view`, `attendance.manage`, `exams.view` |
| **Viewer** | `courses.view`, `groups.view`, `sessions.view`, `subjects.view`, `students.view`, `attendance.view`, `exams.view`, `reports.view` |

⚠️ **Two facts the rest of this doc and the integration guide get wrong — trust this table:**
- **No default role except Owner has any `payments.*` permission.** Out of the box, **only the Owner can see invoices, record payments, or view financial stats.** The Finance section of the UI should be Owner-only unless the Owner has created a custom role (e.g. "Accountant") that grants payment permissions. Do not show Finance to Teacher/Assistant by default.
- **There is no `users.*` / `roles.*` permission in the seeded roles.** Staff & Role management is Owner-only (gated by the `"all"` string), not by a named permission.
- The Teacher role here does **not** include `students.create` — an Assistant can register students but the default Teacher cannot. (Both can manage attendance.)

### 5. QR attendance is a live loop

The teacher generates a QR code for a session. Students scan it, and the teacher's screen polls the live-attendance endpoint every few seconds to see names appear in real time. This is the highest-emotion moment in the product — the UI should feel live. (Polling, not websockets — there is no realtime push channel yet.)

⚠️ The QR token is **valid until the session ends + 30 minutes** (`scheduled_at + duration_minutes + 30`), not a short-lived code. Treat `qr_expires_at` as "scanning closes at this time," not a sub-minute refresh timer.

---

## User Stories

Stories are grouped by role and flow. Each story describes what the user wants and why — plus the API call(s) that serve it and any behavioral rules the UI must handle.

---

### EPIC 1 — Onboarding & Auth

---

**US-01 — Center owner registers their channel**

> *As a center owner, I want to create my channel account so that I can start managing my students and staff.*

**Flow:**
1. Owner opens the app for the first time
2. Fills in center name, type (center or solo teacher), personal info, email, password
3. Receives a 6-digit OTP to their email
4. Enters the OTP → account activated
5. Lands on the dashboard

**API:** `POST /auth/register` → `POST /auth/verify-email` (resend via `POST /auth/resend-otp`)  
**UI notes:**
- Channel type (`center` | `teacher`) affects branding copy throughout the app ("center" shows "your center", "teacher" shows "your workspace")
- After registration the user **cannot log in** until the email is verified (login returns 403) — show an interstitial "check your email" screen with a resend button wired to `POST /auth/resend-otp`
- The OTP is a 6-digit code with a server-side expiry. Verify/resend error cases: invalid OTP → `422`, expired OTP → `422`, already-verified email → `422` (treat "already verified" as success — just send them to login)
- The channel slug is returned in the register response (`channel.slug`) — store it in app state immediately

---

**US-02 — Staff member logs in**

> *As a teacher or assistant, I want to log in and land on the right dashboard for my role.*

**Flow:**
1. Enters email or phone + password
2. Sees their role-appropriate dashboard (teacher sees their groups; assistant sees student list)

**API:** `POST /auth/login`  
**UI notes:**
- Login can use email OR phone — send `email` **or** `phone` plus `password`
- **Login failure codes are distinct — handle each:**
  - `404` — no account with that email/phone (show "No account found", not "wrong password")
  - `401` — wrong password
  - `403` — **overloaded**: either email is unverified **or** the account is blocked/suspended. Distinguish by the `message` text; on the unverified case, redirect to the OTP verification screen. (Note `403` here is *not* a permission error — only post-login API calls use 403 to mean "permission denied".)
- Store `access_token`, `expires_in` (seconds — compute the absolute expiry yourself), and `channel.slug` — all three are needed for every subsequent call
- Schedule a token refresh before the token elapses (e.g. at 80% of the TTL) via `POST /{channel_slug}/auth/refresh`

---

**US-03 — User resets their forgotten password**

> *As any user, I want to reset my password via email so that I'm not locked out permanently.*

**Flow:** Forget password → enter email → OTP sent → enter OTP + new password → redirect to login  
**API:** `POST /auth/forget-password` → `POST /auth/reset-password`

---

### EPIC 2 — Staff & Role Management

---

**US-04 — Owner creates a teacher account**

> *As an owner, I want to add a teacher to my channel so that they can manage their own groups without seeing the financials.*

**Flow:**
1. Owner opens Users → Add user
2. Fills name, email, phone, gender, password, and selects role "Teacher"
3. New user appears in the list

**API:** `GET /roles` (to populate dropdown) → `POST /users`  
**UI notes:**
- The role dropdown must fetch live roles — there may be custom roles the owner created
- Newly created staff users do NOT go through OTP verification flow — they're created directly by the owner

---

**US-05 — Owner creates a custom role**

> *As an owner, I want to create a custom "Accountant" role with only payment permissions so that the accountant sees only what they need.*

**API:** `POST /roles` with `permissions: ["payments.view", "payments.create", "payments.update"]`  
**UI notes:**
- The permissions picker should present all known permissions as checkboxes grouped by module. ⚠️ Source the master permission list from the backend/team rather than hard-coding it — the seeded roles don't reference any `payments.*` strings, so confirm the exact payment permission names with backend before shipping the Accountant flow
- This Accountant example is exactly *why* payments default to Owner-only: granting payment permissions is an explicit, custom act
- The Owner role cannot be deleted or modified (it's the channel-level `null`-scoped seeded role)
- Roles & user management screens are **Owner-only** (gated by the `"all"` permission), not by a `roles.*` string — there is no such default permission

---

### EPIC 3 — Academic Setup

---

**US-06 — Teacher creates a course**

> *As a teacher, I want to create a Math course so that I can organize my groups under it.*

**Flow:** Courses → New course → name, type, subject (optional), cover image  
**API:** `POST /courses` (multipart/form-data if image included)  
**Statuses:** `draft` → teacher is still setting up; `active` → visible to staff; `archived` → hidden from new enrollments

---

**US-07 — Teacher creates a group inside a course**

> *As a teacher, I want to create a group "Mon/Wed 4 PM" under my Math course so that I can enroll students and schedule sessions.*

**Key fields:** name, `payment_model` (monthly/per_course/per_session), `starts_at`, `ends_at`, max capacity  
**UI notes:**
- The `payment_model` drives the enrollment form later — show the right fee fields based on what's selected
- A group with `status: "full"` should show a visual indicator and block new enrollments

---

**US-08 — Teacher schedules sessions for a group**

> *As a teacher, I want to set up the weekly session schedule so that all Monday sessions for the term appear automatically.*

**Flow A (one-off):** Send `scheduled_at` + `duration_minutes` → returns one full session object (`201`).  
**Flow B (recurring):** Send a `recurring_rule` (`day`, `start_time`, `end_time`) → backend generates 90 days of instances and returns `{ session_time_id, message: "Recurring sessions queued for 90 days" }` (`201`) — **not** the list of instances.  
**API:** `POST /groups/{group}/sessions`  
**UI notes:**
- The recurring response does **not** include the generated instances. To show a "calendar preview," the client must either compute the expected dates locally from the rule, or re-fetch `GET /groups/{group}/sessions` after creation to list what was generated. There's no dry-run/preview endpoint
- Session `type` is `online` or `offline` only (no `hybrid` at the session level — `hybrid` exists only on the parent Course)
- A session can be **edited only if `status: "scheduled"`** (the API returns 422 otherwise). Cancelling a `completed` session is also blocked (422). Show PATCH/DELETE controls accordingly
- Status badge colors: `scheduled` = neutral, `live` = green pulse, `completed` = muted, `cancelled` = red strikethrough

---

### EPIC 4 — Student Management

---

**US-09 — Assistant registers a new student**

> *As an assistant, I want to register a new student so that they can be enrolled in groups.*

**Required fields:** name, gender  
**Optional:** email, phone, birth date, national ID, address, photo  
**API:** `POST /students`  
**UI notes:**
- The student code (`code` field) is auto-generated by the backend — don't ask the user for it
- Duplicate phone/email within the same channel returns 422 with field-level errors

---

**US-10 — Assistant adds a guardian**

> *As an assistant, I want to link a parent's phone number to a student so that we can contact them.*

**API:** `POST /students/{student}/guardians`  
**UI notes:**
- Show a "Primary contact" toggle — only one guardian can be primary at a time
- Setting a new guardian as primary automatically demotes the previous one (server-side, but the UI should reflect it optimistically)
- `relationship` enum should be rendered as Arabic-friendly labels: أب / أم / ولي أمر / آخر

---

**US-11 — Teacher enrolls a student into a group**

> *As a teacher, I want to enroll Ahmed in the Mon/Wed Math group so that he appears in attendance and his first invoice is generated.*

**Key fields:** `student_id`, `group_id`, `enrollment_type`, `start_date`, and the fee field matching the plan: `agreed_monthly_fee` (monthly), `agreed_course_fee` (per_course), or `agreed_session_fee` (per_session)  
**API:** `POST /student-enrollments`  
**UI notes:**
- **Only `monthly` enrollments auto-create a prorated first invoice.** For a monthly plan, show a toast: "Enrollment saved. First invoice of EGP X created automatically." For `per_course`/`per_session`, no invoice is created — don't show that toast.
- **Proration math (monthly only):** the prorated amount is `fee × (daysRemaining / daysInMonth)`, where `daysRemaining = daysInMonth − startDay + 1` (the start day counts as a billable day). If `start_date` is July 15 and the fee is EGP 500: `daysRemaining = 31 − 15 + 1 = 17`, so the first invoice is **500 × 17/31 ≈ EGP 274.19**. If the student starts on the 1st, it's the full fee (no proration). Show this math in a preview before submitting.
- The frontend should align `enrollment_type` with the group's `payment_model` in the UI (pre-select it from the group). **Note:** the backend does not currently reject a mismatch, so this is a UX guard, not a guarantee — don't rely on a server-side mismatch error.

---

### EPIC 5 — Attendance

---

**US-12 — Teacher takes attendance manually**

> *As a teacher, I want to mark attendance for all students in a session at once so that I don't have to do it one by one.*

**Flow:**
1. Teacher opens a session → tap "Take attendance"
2. Student list appears, each with status buttons: Present / Late / Absent / Excused
3. Default is Absent (safe default — students must be marked present)
4. Teacher saves → bulk POST

**API:** `POST /attendances/bulk` — body is `{ session_id, attendances: [{ student_id, group_id, status }, ...] }`  
**UI notes:**
- Pre-populate the list from the group's enrolled students. Each row must carry its own `group_id` in the payload, and **the backend rejects any student who isn't enrolled in that group** (422, field error on `student_id`) — so only list enrolled students
- The "Absent by default" rule is a **UI** choice and a good one (forces deliberate marking), but **always send an explicit `status` for every row** — if a row is sent with no status the backend silently defaults it to `present`, which is the opposite of what we want
- Show a count in the header: "8 / 12 marked"
- A student can't be marked twice for the same session — re-posting a duplicate returns a validation error. Once saved, status chips go read-only unless the user clicks "Edit" (which uses `PUT /attendances/{id}`, not bulk)

---

**US-13 — Teacher displays the QR code for a session**

> *As a teacher, I want to show a QR code on the classroom screen so that students can scan it to mark themselves present.*

**Flow:**
1. Teacher taps "Generate QR" on a session
2. QR code appears full-screen (or on a large card)
3. A timer counts down to expiry
4. Teacher can regenerate at any time — old QR immediately stops working

**API:** `POST /groups/{group}/sessions/{session}/qr` (returns `200` with `{ qr_token, qr_expires_at, session_id }`)  
**UI notes:**
- The `qr_token` string must be encoded into a QR image client-side (use a QR library like `qrcode.js`)
- `qr_expires_at` = session end + 30 min. Show it as a deadline ("Scanning open until 5:30 PM") rather than a fast countdown — the window is hours, not minutes
- After regeneration, the previous token stops working immediately — show a flash: "QR refreshed". (Only the latest token is accepted; an old QR returns invalid-token on scan.)

---

**US-14 — Student scans QR to check in**

> *As a student, I want to scan the QR code with my phone so that my attendance is recorded without the teacher doing it.*

**Flow:**
1. Student opens the student app / web scanner
2. Camera scans the QR
3. Sends `{ token, student_id }` to the API
4. Screen shows: ✓ "Attendance recorded — Present" or ⏱ "You're 18 minutes late — marked Late"

**API:** `POST /attendances/qr-scan` with `{ token, student_id }`  
**UI error handling:**

| API response | UI message to student |
|---|---|
| 201 — `status: present` | "✓ You're on time. Attendance recorded." |
| 201 — `status: late` | "⏱ You're late. Marked as Late." |
| 409 — blocked absent | "Your teacher has marked you absent for this session. Contact them to correct it." |
| 400 — invalid token | "This QR code is invalid. Ask your teacher to regenerate it." |
| 400 — expired token | "This QR code has expired. Ask your teacher to regenerate it." |
| 422 — already checked in | "You've already checked in for this session." |

⚠️ **Status codes corrected to match the backend:** an **expired** token returns **`400`** (same as a tampered/invalid one), not 422. The two share a status code, so distinguish them by the `message` text, not the code. The 201 `late` response records the attendance but the API does **not** return a "minutes late" number — don't promise "N minutes late" in the copy; just say "Marked as Late."

⚠️ This endpoint requires an authenticated caller and an explicit `student_id` in the body — there is no anonymous public scan route, and no student-side app exists yet to call it (see the build-scope note at the top). Until the Student Portal ships, the only realistic caller is a staff device scanning on a student's behalf.

---

**US-15 — Teacher watches live attendance fill in**

> *As a teacher, I want to see student names appear on my screen as they scan in so that I know who's here without counting.*

**Flow:** Teacher leaves the QR screen open. The attendance list below the QR updates every few seconds.  
**API:** `GET /sessions/{session}/attendance` — poll every 5–10 seconds. Returns a `summary` object `{ total, present, late, absent, excused }`, a `session` object (`scheduled_at`, `status`, `qr_expires_at`), and the full `attendance` list.  
**UI notes:**
- Each new check-in animates into the list (the list grows as scans land between polls — there is no push/websocket, so newness is detected client-side by diffing poll results)
- Show a live counter from `summary`: "Present: 7 · Late: 2 · Excused: 1 · Absent: 3" — surface all four buckets, not just three
- A student manually marked `absent` cannot be flipped by a later scan (the scan returns 409). Render those rows as locked so staff understand why a scan "didn't work" for them

---

### EPIC 6 — Payments & Invoices

---

**US-16 — Assistant views a student's outstanding invoices**

> *As an assistant, I want to see which invoices Ahmed has not paid so that I can remind his guardian.*

**API:** `GET /invoices/student/{id}` + `GET /invoices/overdue` (also `GET /invoices/pending`)  
**UI notes:**
- ⚠️ **Invoice `status` enum is only `pending`, `paid`, `overdue`, `cancelled` — there is no `partially_paid` status.** A partial payment keeps the invoice `pending` and just lowers `remaining_amount`. Show chips: `pending` = amber, `paid` = green, `overdue` = red, `cancelled` = grey. To convey "partially paid," derive it client-side: `status === 'pending' && paid_amount > 0` → show a "Partial" badge / progress bar, but don't expect a server status for it
- **`remaining_amount` is the source of truth for what's owed** — not `total_amount` or `final_amount`. (`final_amount` = total − discount; `paid_amount` + `remaining_amount` = `final_amount`.)

---

**US-17 — Assistant records a payment**

> *As an assistant, I want to record that Ahmed paid EGP 300 in cash against his July invoice so that the balance updates.*

**Flow:**
1. Open Ahmed's invoices
2. Select the invoice → "Record payment"
3. Enter amount + payment method + date
4. Save → invoice remaining balance updates

**API:** `POST /payments`  
**UI notes:**
- Amount must not exceed `invoice.remaining_amount` — validate before submit and show "Max: EGP X"
- Overpayment returns 422 — surface this as a clear inline error, not a generic toast
- After saving, re-fetch the invoice to show the updated `remaining_amount` and `status`

---

**US-18 — Owner creates an ad-hoc fee**

> *As an owner, I want to charge Ahmed EGP 150 for a textbook so that it appears as a separate invoice.*

**API:** `POST /invoices` with `type: "ad_hoc"` and `reason: "Textbook fee Q3"`  
**UI notes:**
- `reason` is **required** for ad-hoc invoices (`required_if:type,ad_hoc`, max 500 chars) — show it immediately when "Ad-hoc fee" is selected from the type dropdown
- The other invoice types (`monthly`, `session`, `enrollment_fee`) are system-generated and usually shouldn't be created manually from the UI — consider hiding them from the manual creation dropdown
- Other server-side constraints to validate in the form: `total_amount` ≥ 0.01; `due_date` must be **today or later** (`after_or_equal:today`); `discount_amount` cannot exceed `total_amount`
- This is an Owner-only action by default (payments permission) — see Permissions

---

**US-19 — Owner views financial statistics**

> *As an owner, I want to see total collected revenue for the month so that I know how the business is doing.*

**API:** `GET /payments/statistics?start_date=2026-07-01&end_date=2026-07-31`  
**UI notes:**
- Date range picker defaults to current month
- Show: total invoiced, total collected, total outstanding, collection rate %

---

### EPIC 7 — Exams

---

**US-20 — Teacher creates an exam**

> *As a teacher, I want to create a midterm exam for my Math group so that students can take it digitally.*

**Flow:**
1. Navigate to group → Exams → New exam
2. Set title, total marks, pass marks, duration, time window (`starts_at` / `ends_at`), retake rules
3. Exam saved as `draft`

**API:** `POST /exams`  
**UI notes:**
- `allow_retake` + `max_attempts` should be a paired control: toggle "Allow retakes" → reveals "Max attempts" number input
- While status is `draft`, show a banner: "This exam is not visible to students yet"

---

**US-21 — Teacher adds questions to an exam**

> *As a teacher, I want to add MCQ and essay questions so that the exam has a mix of auto-graded and manual questions.*

**Flow:**
1. Open exam → Questions tab → Add question
2. Select type → form adapts:
   - MCQ: question text + 2–4 options with radio to mark correct one
   - True/False: question text + two options pre-labelled "True" / "False"
   - Short answer / Essay: just the question text + marks
3. Drag to reorder (sends updated `order` values)

**API:** `POST /exams/{id}/questions`  
**UI notes:**
- Exactly one option must be marked correct for MCQ and True/False — enforce this client-side (the backend also enforces it)
- Show a running total: "Total marks so far: 65 / 100"
- Questions can be added/edited/deleted while the exam is `draft` **or** `published` — the backend only blocks question changes once the exam is **`closed`** (422). ⚠️ Note this differs from the *exam-level* edit rule: editing the exam record itself (title, marks, dates) is blocked once a published exam has submissions, but adding/editing questions is **not** blocked by submissions — only by `closed` status. Be deliberate about this in the UI: editing questions on a published exam that students are mid-attempt on is allowed by the API but is usually a bad idea — consider warning the teacher rather than relying on a server block.

---

**US-22 — Teacher publishes the exam**

> *As a teacher, I want to publish the exam so that students can see and attempt it.*

**API:** `POST /exams/{id}/publish`  
**UI notes:**
- Block publish if question count is 0 — show: "Add at least one question before publishing"
- After publish, show a confirmation: "Exam is now live. Students can attempt it between [starts_at] and [ends_at]."
- Once published with submissions, the Edit button should be disabled with tooltip: "Can't edit — students have already attempted this exam"

---

**US-23 — Student takes an exam**

> *As a student, I want to open the exam, answer the questions, and submit before time runs out.*

**Flow:**
1. Student opens the exam → sees title, duration, attempt rules
2. Taps "Start exam" → timer begins
3. Answers each question:
   - MCQ / True-False: tap an option
   - Short answer / Essay: type text
4. Taps "Submit"

**API:** `POST /exams/{id}/start` (body `{ student_id }`, returns the submission — its id is the `id` field, e.g. `submission.id`) → answer locally → `POST /exams/{id}/submissions/{sub}/submit`  
**UI notes:**
- Save answers locally as the student types — don't wait for the submit call to persist
- Show a countdown timer based on `duration_minutes`
- On submit, if the exam has **no ungraded essay/short-answer questions**, the submission comes back `status: graded` — show the score immediately: "You scored 78 / 100 — Passed ✓"
- If any essay/short-answer questions remain ungraded, the submission stays `status: submitted` — show: "Answers submitted. Your teacher will review the written questions soon."
- The `is_correct` field is NOT returned while the exam is in progress — never compute the score client-side
- Starting an exam beyond `max_attempts` returns **422** — disable "Start" and show the attempt count when the student is out of attempts

---

**US-24 — Teacher grades an essay exam**

> *As a teacher, I want to read each student's essay answer and assign marks so that their final score is calculated.*

**Flow:**
1. Teacher opens exam → Submissions tab
2. Selects a submission with `status: "submitted"` (essays pending)
3. Reads the essay → enters marks for each essay answer
4. Optionally adds teacher notes
5. Saves → submission recalculates to `status: "graded"`, `is_pass` is set

**API:** `POST /exams/{id}/submissions/{sub}/grade`  
**UI notes:**
- Show the question text, the student's answer, and a marks input (max = `question.marks`)
- After grading, show the student's total and whether they passed

---

**US-25 — Teacher reviews exam results**

> *As a teacher, I want to see how the whole class performed so that I know which topics to revise.*

**API:** `GET /exams/{id}/results`  
**UI notes:**
- Show: pass rate, average score, highest, lowest
- Table of students: name, score, %, pass/fail, attempt number
- Filter by `status` (graded / submitted / in_progress)

---

## Screen map (suggested)

```
App
├── Auth
│   ├── Register
│   ├── Verify OTP
│   ├── Login
│   └── Forgot Password
│
├── Dashboard (role-aware summary cards)
│
├── Users          [owner only]
│   ├── Staff list
│   └── Roles
│
├── Academic
│   ├── Courses
│   │   └── Course detail
│   │       └── Groups
│   │           └── Group detail
│   │               ├── Students (enrolled)
│   │               ├── Sessions
│   │               │   └── Session detail
│   │               │       ├── QR display
│   │               │       └── Live attendance
│   │               └── Exams
│   │                   └── Exam detail
│   │                       ├── Questions
│   │                       └── Submissions
│   └── Subjects
│
├── Students
│   ├── Student list
│   └── Student profile
│       ├── Guardians
│       ├── Enrollments
│       ├── Attendance history
│       └── Invoices & payments
│
├── Attendance
│   ├── Take attendance (bulk)
│   └── Statistics
│
└── Finance        [Owner only by default — see Permissions]
    ├── Invoices (overdue / pending)
    ├── Record payment
    └── Statistics
```

> **Finance gating:** by default only the Owner (`permissions: "all"`) reaches Finance — no seeded staff role has payment permissions. Show this branch only when the user is the Owner or has a custom role granting `payments.*`.

> **Not in scope yet (Phases 10–15):** Student app, Guardian/Parent portal, Assignments, Notifications, Live video. The student-facing branches implied by US-14 (QR self-scan) and US-23 (taking an exam) have **no UI surface to build today** — design the staff screens so they slot in later, but don't build student/parent screens now.

---

## Key behavioral rules to reflect in the UI

| Rule | HTTP | What the UI should do |
|---|---|---|
| QR scan → manual absent | `409` | Show a blocked state — lock icon, cannot retry |
| QR scan > 15 min after `scheduled_at` | `201` (`status: late`) | Green check but amber "Late" badge — no "minutes late" number is returned |
| QR token invalid **or expired** | `400` | Both return 400 — distinguish by `message`, prompt to regenerate |
| QR already checked in | `422` | "You've already checked in" |
| Enrollment mid-month (**monthly only**) | auto | Preview prorated amount (`fee × (daysInMonth − startDay + 1)/daysInMonth`) before confirm; no auto-invoice for per_course/per_session |
| Overpayment | `422` | Inline error on amount field: "Max EGP X" (`remaining_amount`) |
| Partially paid invoice | — | No `partially_paid` status exists — derive from `paid_amount > 0 && status === 'pending'` |
| Ad-hoc invoice missing reason | `422` | Inline required field error on `reason` |
| Exam: 0 questions on publish | `422` | Disable publish button, tooltip explains |
| Exam record edit: published + has submissions | `422` | Disable exam edit, show read-only mode |
| Exam question edit | `422` only when `closed` | Questions stay editable while `draft`/`published` — warn (don't block) on a live published exam |
| Max exam attempts reached | `422` | Disable "Start exam" button, show attempt count |
| Group status "full" | — | Block enroll button, show "Group is full" |
| Manual attendance for non-enrolled student | `422` | Only list enrolled students; always send explicit `status` (missing status defaults to `present` server-side) |
| Owner permissions = "all" string | — | Handle both string and array: `perms === "all" \|\| perms.includes(x)` |
| Login failure | `404`/`401`/`403` | 404 = no account, 401 = wrong password, 403 = unverified or blocked (read `message`) |
