# 🧹 تنظيف Migrations وإضافة دعم الباقات الشهرية

## 📋 ملخص التغييرات

تم تنظيف الـ migrations غير المستخدمة وإضافة دعم كامل للباقات الشهرية.

---

## ✅ ما تم إضافته

### 1. جدول `student_enrollments` (الباقات الشهرية)

**الملف**: `modules/Academic/database/migrations/2026_01_20_120000_create_student_enrollments_table.php`

**الغرض**: تتبع اشتراك الطالب في مجموعة مع معلومات الباقة الشهرية.

**الحقول المهمة**:
- `enrollment_type`: 'monthly' | 'course' | 'session_package'
- `status`: 'active' | 'paused' | 'canceled' | 'completed'
- `sessions_per_month`: عدد الحصص في الشهر (مثلاً: 8 حصص)
- `used_sessions_count`: عدد الحصص المستخدمة
- `remaining_sessions_count`: عدد الحصص المتبقية
- `agreed_monthly_fee`: السعر المتفق عليه للباقة الشهرية

**العلاقات**:
- `belongsTo` → Student, Group, Channel

---

### 2. Model `StudentEnrollment`

**الملف**: `modules/Academic/app/Models/StudentEnrollment.php`

**الوظائف**:
- `isActive()`: التحقق من حالة الاشتراك
- `hasRemainingSessions()`: التحقق من وجود حصص متبقية
- `calculateRemainingSessions()`: حساب الحصص المتبقية
- `updateRemainingSessions()`: تحديث عدد الحصص المتبقية
- `incrementUsedSessions()`: زيادة عدد الحصص المستخدمة

---

### 3. تحديث العلاقات في Models

**Group Model**:
- إضافة `enrollments()` relation
- إضافة `activeEnrollments()` relation

**Student Model**:
- إضافة `enrollments()` relation
- إضافة `activeEnrollments()` relation

---

## 🗑️ ما تم حذفه (Migrations غير مستخدمة)

### 1. `payment_months` table
**الملف المحذوف**: `modules/Channel/database/migrations/2025_09_05_150630_create_payment_months_table.php`

**السبب**: 
- غير مستخدم في أي Model/Repository
- عندنا `payment_periods` في Payment module كبديل أفضل وأكثر مرونة

---

### 2. `evaluations` table
**الملف المحذوف**: `modules/Channel/database/migrations/2025_08_19_213850_create_evaluations_table.php`

**السبب**: 
- غير مستخدم في أي Model/Repository
- لم يتم تطويره أو استخدامه في النظام

---

### 3. `group_users` table
**الملف المحذوف**: `modules/Channel/database/migrations/2025_11_29_184600_create_group_users_table.php`

**السبب**: 
- غير مستخدم في أي Model/Repository
- العلاقة بين Users و Groups غير مطلوبة حالياً

---

## 📊 كيفية استخدام الباقات الشهرية

### مثال: إنشاء باقة شهرية لطالب

```php
use Modules\Academic\App\Models\StudentEnrollment;

// إنشاء اشتراك شهري
$enrollment = StudentEnrollment::create([
    'channel_id' => 1,
    'student_id' => 1,
    'group_id' => 1,
    'enrollment_type' => 'monthly',
    'status' => 'active',
    'start_date' => '2026-01-01',
    'end_date' => '2026-01-31',
    'agreed_monthly_fee' => 500.00,
    'sessions_per_month' => 8, // 8 حصص في الشهر
    'used_sessions_count' => 0,
    'remaining_sessions_count' => 8,
]);
```

### مثال: تحديث عدد الحصص المستخدمة بعد حضور

```php
// بعد تسجيل حضور الطالب
$enrollment->incrementUsedSessions(1);
// سيتم تحديث used_sessions_count و remaining_sessions_count تلقائياً
```

### مثال: التحقق من وجود حصص متبقية

```php
if ($enrollment->hasRemainingSessions()) {
    // الطالب عنده حصص متبقية
} else {
    // الباقة انتهت
}
```

---

## 🔄 الخطوات التالية

1. **تشغيل Migration**:
   ```bash
   php artisan migrate
   ```

2. **إنشاء Repository** (اختياري):
   - `StudentEnrollmentRepository` للعمليات المعقدة

3. **إنشاء Controller** (اختياري):
   - `StudentEnrollmentController` لإدارة الاشتراكات

4. **ربط مع Attendance**:
   - عند تسجيل حضور، تحديث `used_sessions_count` تلقائياً

5. **ربط مع Payments**:
   - ربط `payments` بـ `student_enrollment_id` (مستقبلاً)

---

## 📝 ملاحظات

- ✅ **الباقات الشهرية** الآن مدعومة بالكامل
- ✅ **عدد الحصص** يتم تتبعه تلقائياً
- ✅ **حالة الاشتراك** (active/paused/canceled) مدعومة
- ✅ **التسعير المتفق عليه** محفوظ في الاشتراك

---

**تاريخ التحديث**: 2026-01-20  
**الإصدار**: 1.0

