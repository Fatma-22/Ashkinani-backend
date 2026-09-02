<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $updates = [
            'حمدان انور الشمري' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'ظهير يسار / جناح يمين ويسار',
                'previous_clubs' => '["الوحدة والطائي"]',
                'email' => 'Hamdanalnoor11@gmail.com',
                'phone' => '966532375002',
                'preferred_foot' => 'LEFT',
            ],
            'علي عبيد الدوسري' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'محور6 / ظهير يمين ويسار',
                'previous_clubs' => '["الوشم السعودي"]',
                'email' => 'iphonee.683@icloud.com',
                'phone' => '966533092726',
                'preferred_foot' => 'RIGHT',
            ],
            'عبد المحسن الشمري' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'مدافع سنتر',
                'email' => 'm7ysn2233@gmail.com',
                'phone' => '966502519810',
                'preferred_foot' => 'RIGHT',
            ],
            'محمد هاني عبد الرضا كرم' => [
                'contract_fees' => 200.0,
                'contract_fees_type' => 'FIXED',
                'position' => 'ظهير يمين ويسار/ جناح يمين',
                'previous_clubs' => '["العربي", "القادسية", "اليرموك"]',
                'email' => 'mohammadkaram83@gmail.com',
                'phone' => '96596096911',
                'preferred_foot' => 'RIGHT',
            ],
            'عبد الرحمن بن مبارك بن عبد الله آل يحي' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'ظهير يمين',
                'previous_clubs' => '["الفرسان", "المصيف", "بيشة"]',
                'email' => 'abdullrahman1532@gmail.com',
                'phone' => '966556511328',
                'preferred_foot' => 'RIGHT',
            ],
            'فيصل بن محمد بن ناصر آل شايب' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'قلب دفاع / ظهير ايسر',
                'previous_clubs' => '["الدرعية", "الوشم", "الحريق"]',
                'email' => 'faisalalshaib7@gmail.com',
                'phone' => '966553530151',
                'preferred_foot' => 'BOTH',
            ],
            'منصور حسين منصور الدوسري' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'حارس',
                'previous_clubs' => '["القادسية", "النعيريه"]',
                'email' => 'sikini.33@icluod.com',
                'phone' => '966546556979',
                'preferred_foot' => 'BOTH',
            ],
            'عمار عبد محمد محمد' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'قلب دفاع يمين ويسار',
                'previous_clubs' => '["الجليل", "العقبة", "الاهلي الاردني"]',
                'email' => 'ammarabedmohammad@gmail.com',
                'phone' => '962786650505',
                'preferred_foot' => 'RIGHT',
            ],
            'علي احمد على المطيري.' => [
                'contract_fees' => 200.0,
                'contract_fees_type' => 'FIXED',
                'position' => 'مهاجم / جناح يمين',
                'previous_clubs' => '["الشباب", "السالمية"]',
                'email' => 'ali.a.2001.ali.a@gmail.com',
                'phone' => '96599881058',
                'preferred_foot' => 'RIGHT',
            ],
            'حمزة زياد' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'قلب دفاع/باك وطرف يسار/ارتكاز',
                'previous_clubs' => '["السالمية"]',
                'email' => 'iixluham@gmail.con',
                'phone' => '96566503410',
                'preferred_foot' => 'LEFT',
            ],
            'الحسين محمد البارقي' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'سنتر/محور/ظهير',
                'previous_clubs' => '["القادسية", "الاتفاق", "الاخدود"]',
                'email' => 'xzfrey@gmail.com',
                'phone' => '966510000000',
                'preferred_foot' => 'BOTH',
            ],
            'خالد محمد الحجه' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'قلب دفاع/باك يمين',
                'previous_clubs' => '["حطين", "الكرامه"]',
                'email' => 'alhajjakhaled@gmail.com',
                'phone' => '963999000000',
                'preferred_foot' => 'RIGHT',
            ],
            'ضيدان ناصر عايض العجمي' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'مهاجم/جناح',
                'previous_clubs' => '["الساحل"]',
                'email' => 'alajmid015@gmail.com',
                'phone' => '96599826427',
                'preferred_foot' => 'RIGHT',
            ],
            'احمد يعقوب السعدون' => [
                'contract_fees' => 200.0,
                'contract_fees_type' => 'FIXED',
                'position' => 'LB/CB/LM',
                'previous_clubs' => '["الصليخات", "سبورتي"]',
                'email' => 'ahmaaadyaqoub@gmail.com',
                'phone' => '96597338934',
                'preferred_foot' => 'LEFT',
            ],
            'أندريا باناسيفيتش' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'حارس',
                'previous_clubs' => '["Red Star", "Nachod Fc"]',
                'email' => 'andrabatocina@gmail.com',
                'phone' => '381659000000',
                'preferred_foot' => 'BOTH',
            ],
            'علي الخليفة' => [
                'deal_status' => 'خيطان تحت 14 قادم من السالمية',
            ],
            'إيليا كنيجيفيتش' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'previous_clubs' => '["الكويت", "النصر"]',
                'email' => 'Ilijaikaknezevic@gmail.com',
                'phone' => '96569650235',
            ],
            'عبد العزيز عباس حسن دشتي.' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'email' => 'Azddashti86@gmail.com',
                'phone' => '96599963323',
            ],
            'احمد عبد الرسول يوسف الشيبه' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
            ],
            'خالد البصيري' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'email' => 'Kld11vlc@gmail.com',
                'phone' => '96565588222',
            ],
            'عبد الله يعقوب يوسف بن عون' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
                'position' => 'مساعد مدرب نادي النصر تحت 21',
                'email' => 'Bo3awna@hotmail.com',
                'phone' => '96565500222',
            ],
            'يونس اكبر' => [
                'contract_fees' => 0.3,
                'contract_fees_type' => 'PERCENTAGE',
            ],
        ];

        foreach ($updates as $name => $data) {
            DB::table('players')
                ->where('name_ar', $name)
                ->orWhere('name', $name)
                ->update(array_merge($data, ['updated_at' => now()]));
        }
    }

    public function down(): void
    {
    }
};
