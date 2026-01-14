<?php

namespace Database\Seeders\Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\App\Models\Subject;
use Modules\Academic\App\Models\SubjectTranslations;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds all Egyptian curriculum subjects as general subjects (channel_id = null)
     */
    public function run(): void
    {
        $subjects = [
            // Primary Stage Subjects
            [
                'code' => 'MATH',
                'credits' => 3,
                'is_active' => true,
                'channel_id' => null, // General subject
                'translations' => [
                    'en' => [
                        'name' => 'Mathematics',
                        'description' => 'Mathematics is the study of numbers, quantities, and shapes.',
                    ],
                    'ar' => [
                        'name' => 'الرياضيات',
                        'description' => 'الرياضيات هي دراسة الأرقام والكميات والأشكال.',
                    ],
                ],
            ],
            [
                'code' => 'ARAB',
                'credits' => 3,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Arabic',
                        'description' => 'Arabic language and literature studies.',
                    ],
                    'ar' => [
                        'name' => 'اللغة العربية',
                        'description' => 'دراسات اللغة العربية والأدب.',
                    ],
                ],
            ],
            [
                'code' => 'ENG',
                'credits' => 3,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'English',
                        'description' => 'English language and literature studies.',
                    ],
                    'ar' => [
                        'name' => 'اللغة الإنجليزية',
                        'description' => 'دراسات اللغة الإنجليزية والأدب.',
                    ],
                ],
            ],
            [
                'code' => 'SCI',
                'credits' => 3,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Science',
                        'description' => 'General science including physics, chemistry, and biology.',
                    ],
                    'ar' => [
                        'name' => 'العلوم',
                        'description' => 'العلوم العامة بما في ذلك الفيزياء والكيمياء والأحياء.',
                    ],
                ],
            ],
            [
                'code' => 'SOC',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Social Studies',
                        'description' => 'Social studies including history, geography, and civics.',
                    ],
                    'ar' => [
                        'name' => 'الدراسات الاجتماعية',
                        'description' => 'الدراسات الاجتماعية بما في ذلك التاريخ والجغرافيا والتربية الوطنية.',
                    ],
                ],
            ],
            [
                'code' => 'ISLAM',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Islamic Studies',
                        'description' => 'Islamic religion and culture studies.',
                    ],
                    'ar' => [
                        'name' => 'التربية الإسلامية',
                        'description' => 'دراسات الدين الإسلامي والثقافة.',
                    ],
                ],
            ],
            [
                'code' => 'ART',
                'credits' => 1,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Art',
                        'description' => 'Visual arts and creative expression.',
                    ],
                    'ar' => [
                        'name' => 'الفن',
                        'description' => 'الفنون البصرية والتعبير الإبداعي.',
                    ],
                ],
            ],
            [
                'code' => 'PE',
                'credits' => 1,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Physical Education',
                        'description' => 'Physical education and sports activities.',
                    ],
                    'ar' => [
                        'name' => 'التربية الرياضية',
                        'description' => 'التربية البدنية والأنشطة الرياضية.',
                    ],
                ],
            ],
            // Preparatory Stage Additional Subjects
            [
                'code' => 'PHYS',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Physics',
                        'description' => 'Physics - the study of matter, motion, and energy.',
                    ],
                    'ar' => [
                        'name' => 'الفيزياء',
                        'description' => 'الفيزياء - دراسة المادة والحركة والطاقة.',
                    ],
                ],
            ],
            [
                'code' => 'CHEM',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Chemistry',
                        'description' => 'Chemistry - the study of matter and its properties.',
                    ],
                    'ar' => [
                        'name' => 'الكيمياء',
                        'description' => 'الكيمياء - دراسة المادة وخصائصها.',
                    ],
                ],
            ],
            [
                'code' => 'BIO',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Biology',
                        'description' => 'Biology - the study of living organisms.',
                    ],
                    'ar' => [
                        'name' => 'الأحياء',
                        'description' => 'الأحياء - دراسة الكائنات الحية.',
                    ],
                ],
            ],
            [
                'code' => 'HIST',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'History',
                        'description' => 'Study of past events and civilizations.',
                    ],
                    'ar' => [
                        'name' => 'التاريخ',
                        'description' => 'دراسة الأحداث والحضارات السابقة.',
                    ],
                ],
            ],
            [
                'code' => 'GEO',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Geography',
                        'description' => 'Study of the Earth and its features.',
                    ],
                    'ar' => [
                        'name' => 'الجغرافيا',
                        'description' => 'دراسة الأرض وخصائصها.',
                    ],
                ],
            ],
            [
                'code' => 'CIV',
                'credits' => 1,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Civics',
                        'description' => 'Civics and national education.',
                    ],
                    'ar' => [
                        'name' => 'التربية الوطنية',
                        'description' => 'التربية الوطنية والمواطنة.',
                    ],
                ],
            ],
            [
                'code' => 'FRENCH',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'French',
                        'description' => 'French language studies.',
                    ],
                    'ar' => [
                        'name' => 'اللغة الفرنسية',
                        'description' => 'دراسات اللغة الفرنسية.',
                    ],
                ],
            ],
            [
                'code' => 'GERMAN',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'German',
                        'description' => 'German language studies.',
                    ],
                    'ar' => [
                        'name' => 'اللغة الألمانية',
                        'description' => 'دراسات اللغة الألمانية.',
                    ],
                ],
            ],
            [
                'code' => 'ITALIAN',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Italian',
                        'description' => 'Italian language studies.',
                    ],
                    'ar' => [
                        'name' => 'اللغة الإيطالية',
                        'description' => 'دراسات اللغة الإيطالية.',
                    ],
                ],
            ],
            [
                'code' => 'SPANISH',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Spanish',
                        'description' => 'Spanish language studies.',
                    ],
                    'ar' => [
                        'name' => 'اللغة الإسبانية',
                        'description' => 'دراسات اللغة الإسبانية.',
                    ],
                ],
            ],
            // Secondary Stage Additional Subjects
            [
                'code' => 'PHIL',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Philosophy',
                        'description' => 'Philosophy and logic studies.',
                    ],
                    'ar' => [
                        'name' => 'الفلسفة',
                        'description' => 'دراسات الفلسفة والمنطق.',
                    ],
                ],
            ],
            [
                'code' => 'PSYCH',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Psychology',
                        'description' => 'Psychology and human behavior studies.',
                    ],
                    'ar' => [
                        'name' => 'علم النفس',
                        'description' => 'دراسات علم النفس وسلوك الإنسان.',
                    ],
                ],
            ],
            [
                'code' => 'ECON',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Economics',
                        'description' => 'Economics and business studies.',
                    ],
                    'ar' => [
                        'name' => 'الاقتصاد',
                        'description' => 'دراسات الاقتصاد والأعمال.',
                    ],
                ],
            ],
            [
                'code' => 'STAT',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Statistics',
                        'description' => 'Statistics and data analysis.',
                    ],
                    'ar' => [
                        'name' => 'الإحصاء',
                        'description' => 'الإحصاء وتحليل البيانات.',
                    ],
                ],
            ],
            [
                'code' => 'COMP',
                'credits' => 2,
                'is_active' => true,
                'channel_id' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Computer Science',
                        'description' => 'Computer science and programming.',
                    ],
                    'ar' => [
                        'name' => 'علوم الحاسب',
                        'description' => 'علوم الحاسب والبرمجة.',
                    ],
                ],
            ],
        ];

        foreach ($subjects as $subjectData) {
            $translations = $subjectData['translations'];
            unset($subjectData['translations']);

            // For general subjects (channel_id = null), use code only for uniqueness
            // For channel-specific subjects, use code and channel_id
            $uniqueKey = $subjectData['channel_id'] === null 
                ? ['code' => $subjectData['code'], 'channel_id' => null]
                : ['code' => $subjectData['code'], 'channel_id' => $subjectData['channel_id']];

            $subject = Subject::updateOrCreate(
                $uniqueKey,
                $subjectData
            );

            // Create translations
            foreach ($translations as $locale => $translationData) {
                SubjectTranslations::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'locale' => $locale,
                    ],
                    $translationData
                );
            }
        }
    }
}
