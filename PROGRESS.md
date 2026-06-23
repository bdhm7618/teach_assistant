# Channel Project — Implementation Progress

Last updated: 2026-06-17

## Phase 0 — Architecture Fixes

| Sub-phase | Name | Status | Done |
|-----------|------|--------|------|
| A | Slug-based Routing | ✅ Done | 2026-06-07 |
| B | Course Model | ✅ Done | 2026-06-07 |
| C | Session Instances | ✅ Done | 2026-06-07 |

## Pending Phases

| Phase | Name | Status | Depends On |
|-------|------|--------|------------|
| P1 | Complete Auth (OTP, email verify) | ✅ Done | 2026-06-08 |
| P2 | RBAC Completion | ✅ Done | 2026-06-09 |
| P3 | Subject Management | ✅ Done | 2026-06-09 |
| P4 | Course + Group CRUD | ✅ Done (P0-B) | — |
| P5 | Parent / Guardian | ✅ Done | 2026-06-09 |
| P6 | Sessions (dated instances) | ✅ Done (P0-C) | — |
| P7 | Enrollment + Payment Dues | ✅ Done | 2026-06-17 |
| P8 | Attendance QR + Realtime | ✅ Done | 2026-06-17 |
| P9 | Exam Module | ✅ Done | 2026-06-21 |
| P10 | Assignment Module | ⬜ Pending | P9 |
| P11 | Notifications | ⬜ Pending | P7 |
| P12 | Student Portal | ⬜ Pending | P7, P9, P10 |
| P13 | Parent Portal | ⬜ Pending | P5, P12 |
| P14 | Live Sessions (WebRTC) | ⬜ Pending | P8 |
| P15 | Platform Admin | ⬜ Pending | All |

## Locked Decisions

| Decision | Value |
|---|---|
| Auth driver | JWT (tymon/jwt-auth v2) |
| Late threshold | 15 min after `scheduled_at` |
| QR after manual absent | BLOCK — no override |
| Overpayment | REJECT |
| Exam retakes | Configurable per exam (`allow_retake` + `max_attempts`) |
| Exam essay auto-grade | Objective (MCQ/T-F) auto-graded on submit; essay/short-answer requires teacher grade |
| Live recording Phase 1 | DEFER |
| Notifications | Email only |
| Mid-month enrollment | PRORATE |
| Ad-hoc dues | ALLOW with `reason` field |
