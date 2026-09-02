<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $updates = [
            'هاني سالم محمد الصقر' => [
                'deal_status' => 'انتهى',
            ],
            'محمد صهيب أمين' => [
                'contract_fees' => 250.0,
                'contract_fees_type' => 'FIXED',
                'deal_status' => 'اليرموك',
            ],
            'عبد الله عايض العتيبي' => [
                'contract_fees' => 250.0,
                'contract_fees_type' => 'FIXED',
            ],
            'سليم بن هادي العياري' => [
                // No specific data provided in image/text other than name
            ],
            'عبد الرحمن عبد العزيز البلوشي' => [
                'contract_fees' => 300.0,
                'contract_fees_type' => 'FIXED',
                'deal_status' => 'خيطان',
            ],
            'طارق بن نور الدين' => [
                // No specific data
            ],
            'علي العماري محمد' => [
                // No specific data
            ],
            'حمزة فاضل محمد الصراف' => [
                // No specific data
            ],
            'يوسف عبد الكريم تقي علي' => [
                'contract_fees' => 200.0,
                'contract_fees_type' => 'FIXED',
            ],
            'رافع عبد الرزاق هرابي' => [
                'contract_fees' => 200.0,
                'contract_fees_type' => 'FIXED',
                'deal_status' => 'العربي',
            ],
            'عبد الله محمد حمد محمد محمود' => [
                'contract_fees' => 350.0,
                'contract_fees_type' => 'FIXED',
                'deal_status' => 'الجهراء',
            ],
            'الطربلسي مدرب حراس' => [
                'deal_status' => 'الجهراء',
            ],
            'علي خليفة عبد السيد العلي' => [
                'contract_fees' => 10.0,
                'contract_fees_type' => 'PERCENTAGE',
            ],
        ];

        foreach ($updates as $name => $data) {
            if (empty($data))
                continue; // Skip if no updates

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
