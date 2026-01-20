# 📚 Core Documentation - Teachify System

هذا المجلد يحتوي على التوثيق الشامل لنظام Teachify.

---

## 📁 الملفات المتوفرة

### 1. `DATABASE_SCHEMA.md`
- ERD Diagram كامل
- شرح جميع الجداول
- العلاقات بين الجداول
- API Endpoints

### 2. `BUSINESS_REQUIREMENTS.md`
- متطلبات الأعمال (BR1-BR10)
- الأولويات
- التفاصيل التقنية

### 3. `USER_STORIES.md`
- قصص المستخدم منظمة حسب الوظيفة
- المعايير لكل قصة

---

## 🚀 Quick Start

1. **قراءة التصميم**: ابدأ بـ `DATABASE_SCHEMA.md`
2. **فهم المتطلبات**: راجع `BUSINESS_REQUIREMENTS.md`
3. **فهم الاستخدام**: راجع `USER_STORIES.md`

---

## 📊 Database Schema Overview

### Core Tables
- `channels` - القنوات/السنترات
- `users` - المستخدمين
- `roles` - الأدوار

### Academic Tables
- `academic_years` - السنوات الدراسية (اختياري)
- `class_grades` - الفصول
- `subjects` - المواد
- `groups` - المجموعات
- `session_times` - أوقات الحصص
- `group_users` - المدرسين/المساعدين في المجموعات ✅ **جديد**
- `student_enrollments` - الباقات الشهرية ✅ **جديد**

### Student Tables
- `students` - الطلاب
- `group_students` - علاقة الطلاب بالمجموعات

### Attendance Tables
- `attendances` - الحضور والغياب

### Payment Tables
- `payment_periods` - فترات التحصيل
- `invoices` - الفواتير
- `payments` - المدفوعات
- `installments` - الأقساط
- `discounts` - الخصومات

---

## 🔌 API Endpoints

### Student Enrollments ✅ **جديد**
- `GET /api/v1/academic/student-enrollments` - List enrollments
- `POST /api/v1/academic/student-enrollments` - Create enrollment
- `GET /api/v1/academic/students/{id}/enrollments` - Get student enrollments
- `GET /api/v1/academic/groups/{id}/enrollments` - Get group enrollments

### Group Users ✅ **جديد**
- `GET /api/v1/academic/groups/{id}/users` - Get group users
- `POST /api/v1/academic/groups/{id}/users` - Assign user to group
- `PUT /api/v1/academic/groups/{id}/users/{userId}` - Update user role
- `DELETE /api/v1/academic/groups/{id}/users/{userId}` - Remove user

---

## ✅ التغييرات الأخيرة (2026-01-20)

1. ✅ إضافة جدول `student_enrollments` (الباقات الشهرية)
2. ✅ إضافة جدول `group_users` (المدرسين/المساعدين)
3. ✅ تحديث Models والعلاقات
4. ✅ تنفيذ APIs جديدة
5. ✅ تنظيف migrations غير المستخدمة
6. ✅ إنشاء توثيق شامل

---

**آخر تحديث**: 2026-01-20

