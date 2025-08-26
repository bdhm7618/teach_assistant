<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subjects')->insert([

            ['code' => 'AR', 'name' => 'اللغة العربية', 'description' => 'مادة أساسية في جميع المراحل التعليمية', 'credits' => 3, 'is_active' => true],
            ['code' => 'EN', 'name' => 'اللغة الإنجليزية', 'description' => 'اللغة الأجنبية الأولى', 'credits' => 3, 'is_active' => true],
            ['code' => 'FR', 'name' => 'اللغة الفرنسية', 'description' => 'اللغة الأجنبية الثانية', 'credits' => 2, 'is_active' => true],
            ['code' => 'MATH', 'name' => 'الرياضيات', 'description' => 'تشمل الحساب والجبر والهندسة والإحصاء', 'credits' => 4, 'is_active' => true],
            ['code' => 'SCI', 'name' => 'العلوم', 'description' => 'علوم عامة في المرحلة الابتدائية والإعدادية', 'credits' => 4, 'is_active' => true],
            ['code' => 'BIO', 'name' => 'الأحياء', 'description' => 'تدرس بعمق في المرحلة الثانوية (شعبة علمي علوم)', 'credits' => 4, 'is_active' => true],
            ['code' => 'CHEM', 'name' => 'الكيمياء', 'description' => 'التفاعلات الكيميائية، مادة أساسية بالمرحلة الثانوية', 'credits' => 4, 'is_active' => true],
            ['code' => 'PHY', 'name' => 'الفيزياء', 'description' => 'قوانين الطبيعة والظواهر الفيزيائية', 'credits' => 4, 'is_active' => true],
            ['code' => 'GEO_SCI', 'name' => 'الجيولوجيا وعلوم البيئة', 'description' => 'علم الأرض والتغيرات البيئية (ثانوي علمي)', 'credits' => 3, 'is_active' => true],
            ['code' => 'SS', 'name' => 'الدراسات الاجتماعية', 'description' => 'تاريخ وجغرافيا في الابتدائي والإعدادي', 'credits' => 3, 'is_active' => true],
            ['code' => 'HIST', 'name' => 'التاريخ', 'description' => 'دراسة التاريخ المصري والعالمي', 'credits' => 3, 'is_active' => true],
            ['code' => 'GEO', 'name' => 'الجغرافيا', 'description' => 'الجغرافيا الطبيعية والسياسية', 'credits' => 3, 'is_active' => true],
            ['code' => 'PHIL', 'name' => 'الفلسفة والمنطق', 'description' => 'مادة للشعبة الأدبية بالمرحلة الثانوية', 'credits' => 3, 'is_active' => true],
            ['code' => 'PSY_SOC', 'name' => 'علم النفس والاجتماع', 'description' => 'سلوكيات الإنسان والعلاقات الاجتماعية', 'credits' => 3, 'is_active' => true],
            ['code' => 'ECON', 'name' => 'الاقتصاد', 'description' => 'أساسيات الاقتصاد والمجتمع', 'credits' => 2, 'is_active' => true],
            ['code' => 'REL', 'name' => 'التربية الدينية', 'description' => 'تعاليم دينية وقيم أخلاقية (للتعليم العام)', 'credits' => 2, 'is_active' => true],
            ['code' => 'ART', 'name' => 'التربية الفنية', 'description' => 'رسم وفنون تشكيلية', 'credits' => 1, 'is_active' => true],
            ['code' => 'MUS', 'name' => 'التربية الموسيقية', 'description' => 'تعليم وتدريب موسيقي', 'credits' => 1, 'is_active' => true],
            ['code' => 'PE', 'name' => 'التربية الرياضية', 'description' => 'أنشطة بدنية ولياقة', 'credits' => 1, 'is_active' => true],
            ['code' => 'ICT', 'name' => 'الحاسب الآلي', 'description' => 'تكنولوجيا المعلومات', 'credits' => 2, 'is_active' => true],
            ['code' => 'THEA', 'name' => 'المسرح', 'description' => 'التربية المسرحية وفنون الأداء', 'credits' => 1, 'is_active' => true],
            ['code' => 'HE', 'name' => 'الاقتصاد المنزلي', 'description' => 'مهارات الحياة اليومية وإدارة الموارد', 'credits' => 1, 'is_active' => true],
            ['code' => 'CIT', 'name' => 'التربية الوطنية', 'description' => 'مواد المواطنة والهوية الوطنية', 'credits' => 1, 'is_active' => true],

            ['code' => 'QUR', 'name' => 'القرآن الكريم', 'description' => 'حفظ وتلاوة القرآن الكريم وتفسيره', 'credits' => 4, 'is_active' => true],
            ['code' => 'TAJ', 'name' => 'التجويد', 'description' => 'أحكام تلاوة القرآن الكريم وضبط مخارج الحروف', 'credits' => 3, 'is_active' => true],
            ['code' => 'HAD', 'name' => 'الحديث الشريف', 'description' => 'دراسة أحاديث النبي صلى الله عليه وسلم وشرحها', 'credits' => 3, 'is_active' => true],
            ['code' => 'FIQH', 'name' => 'الفقه', 'description' => 'أحكام الشريعة الإسلامية حسب المذهب الأزهري', 'credits' => 3, 'is_active' => true],
            ['code' => 'TAFSIR', 'name' => 'التفسير', 'description' => 'شرح معاني آيات القرآن الكريم وأسباب النزول', 'credits' => 3, 'is_active' => true],
            ['code' => 'AQE', 'name' => 'العقيدة', 'description' => 'العقيدة الإسلامية وأركان الإيمان', 'credits' => 3, 'is_active' => true],
            ['code' => 'BAL', 'name' => 'البلاغة', 'description' => 'الأساليب البلاغية في اللغة العربية', 'credits' => 2, 'is_active' => true],
            ['code' => 'NAHW', 'name' => 'النحو والصرف', 'description' => 'قواعد اللغة العربية صرفًا ونحوًا', 'credits' => 3, 'is_active' => true],
            ['code' => 'ARAD', 'name' => 'الأدب والنصوص', 'description' => 'النصوص الأدبية والشعر والنثر العربي', 'credits' => 3, 'is_active' => true],
            ['code' => 'USUL', 'name' => 'أصول الفقه', 'description' => 'القواعد الأصولية لاستنباط الأحكام الشرعية', 'credits' => 3, 'is_active' => true],
            ['code' => 'FIQH_M', 'name' => 'الفقه المقارن', 'description' => 'مقارنة بين المذاهب الفقهية الإسلامية', 'credits' => 3, 'is_active' => true],
            ['code' => 'MAN', 'name' => 'المنطق', 'description' => 'مقدمة في علم المنطق ودوره في الفكر الإسلامي', 'credits' => 2, 'is_active' => true],
        ]);
    }
}
