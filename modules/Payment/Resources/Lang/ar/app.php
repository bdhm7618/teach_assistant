<?php

return [
    'created' => 'تم إنشاء الدفع بنجاح.',
    'updated' => 'تم تحديث الدفع بنجاح.',
    'deleted' => 'تم حذف الدفع بنجاح.',
    'not_found' => 'الدفع غير موجود.',
    'operation_failed' => 'حدث خطأ ما! يرجى المحاولة مرة أخرى.',
    'list_success' => 'تم استرجاع المدفوعات بنجاح.',
    'completed' => 'تم تأكيد الدفع بنجاح.',
    'refunded' => 'تم استرداد الدفع بنجاح.',
    'statistics_retrieved' => 'تم استرجاع الإحصائيات بنجاح.',
    'summary_retrieved' => 'تم استرجاع الملخص بنجاح.',

    'status' => [
        'pending' => 'قيد الانتظار',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
        'refunded' => 'مسترد',
        'cancelled' => 'ملغي',
    ],

    'method' => [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'vodafone_cash' => 'فودافون كاش',
        'orange_money' => 'أورنج موني',
        'etisalat_cash' => 'اتصالات كاش',
        'easy_pay' => 'إيزي باي',
        'credit_card' => 'بطاقة ائتمان',
        'debit_card' => 'بطاقة خصم',
        'online' => 'دفع إلكتروني',
        'other' => 'أخرى',
    ],

    'invoice' => [
        'created' => 'تم إنشاء الفاتورة بنجاح.',
        'updated' => 'تم تحديث الفاتورة بنجاح.',
        'deleted' => 'تم حذف الفاتورة بنجاح.',
        'not_found' => 'الفاتورة غير موجودة.',
        'created_with_installments' => 'تم إنشاء الفاتورة مع الأقساط بنجاح.',
    ],

    'discount' => [
        'created' => 'تم إنشاء الخصم بنجاح.',
        'updated' => 'تم تحديث الخصم بنجاح.',
        'deleted' => 'تم حذف الخصم بنجاح.',
        'not_found' => 'الخصم غير موجود.',
        'invalid' => 'رمز الخصم غير صالح أو منتهي الصلاحية.',
        'applied' => 'تم تطبيق الخصم بنجاح.',
        'code_exists' => 'رمز الخصم هذا موجود بالفعل.',
        'percentage_max' => 'لا يمكن أن يتجاوز الخصم النسبي 100%.',
        'min_amount' => 'الحد الأدنى للمبلغ لهذا الخصم هو :amount.',
    ],

    'validation' => [
        'reference_required' => 'رقم المرجع مطلوب لطريقة الدفع هذه.',
    ],

    'period' => [
        'created' => 'تم إنشاء فترة الدفع بنجاح.',
        'updated' => 'تم تحديث فترة الدفع بنجاح.',
        'deleted' => 'تم حذف فترة الدفع بنجاح.',
        'not_found' => 'فترة الدفع غير موجودة.',
        'no_current_period' => 'لا توجد فترة دفع نشطة للتاريخ الحالي.',
        'month_year_required' => 'الشهر والسنة مطلوبان للفترات الشهرية.',
        'monthly' => 'شهري',
        'weekly' => 'أسبوعي',
        'daily' => 'يومي',
        'session' => 'لكل جلسة',
        'custom' => 'مخصص',
        'monthly_desc' => 'فترة دفع شهرية',
        'weekly_desc' => 'فترة دفع أسبوعية',
        'daily_desc' => 'فترة دفع يومية',
        'session_desc' => 'دفع لكل جلسة درس',
        'custom_desc' => 'فترة دفع مخصصة',
        'month_name' => ':month :year',
        'week_name' => 'أسبوع :start - :end',
        'session_period' => 'فترة الجلسة',
        'custom_period' => 'فترة مخصصة',
    ],
];

