<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        $players = [
            [
                'name_ar' => 'فواز الشمري',
                'sport' => 'Handball',
                'national_id' => null,
                'date_of_birth' => null,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => null,
                'contract_end_date' => null,
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'عبدالعزيز النجدي',
                'sport' => 'Handball',
                'national_id' => null,
                'date_of_birth' => null,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => null,
                'contract_end_date' => null,
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'علي حسين الشمري',
                'sport' => 'Handball',
                'national_id' => null,
                'date_of_birth' => null,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => null,
                'contract_end_date' => null,
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'فواز يوسف',
                'sport' => 'Handball',
                'national_id' => null,
                'date_of_birth' => null,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => null,
                'contract_end_date' => null,
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'مستورة حسن الرشيدي',
                'sport' => 'Basketball',
                'national_id' => '294070300339',
                'date_of_birth' => 1994,
                'nationality_ar' => 'كويتية',
                'gender' => 'FEMALE',
                'contract_duration' => 1,
                'contract_start_date' => '2025-06-09',
                'contract_end_date' => '2026-06-09',
                'contract_fees' => 200,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'حنين أحمد فاخر',
                'sport' => 'Karate',
                'national_id' => null,
                'date_of_birth' => 2008,
                'nationality_ar' => 'كويتية',
                'gender' => 'FEMALE',
                'contract_duration' => 1,
                'contract_start_date' => '2025-10-07',
                'contract_end_date' => '2026-10-07',
                'contract_fees' => 200,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'مهدي أنور عبد الخضر على',
                'sport' => 'Football',
                'national_id' => '310041401074',
                'date_of_birth' => 2010,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => '2025-11-23',
                'contract_end_date' => '2026-11-23',
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'علي حسين على أشكناني',
                'sport' => 'Athletics',
                'national_id' => '307112800965',
                'date_of_birth' => 2007,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => '2025-12-10',
                'contract_end_date' => '2026-12-10',
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
            [
                'name_ar' => 'عيسى عقيل عيسى بوصخر',
                'sport' => 'Football',
                'national_id' => '308022500672',
                'date_of_birth' => 2008,
                'nationality_ar' => 'كويتي',
                'gender' => 'MALE',
                'contract_duration' => 1,
                'contract_start_date' => '2025-12-14',
                'contract_end_date' => '2026-12-14',
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
            ],
        ];

        $nationalities = [
            'كويتي' => 'Kuwaiti',
            'كويتية' => 'Kuwaiti',
        ];

        foreach ($players as $data) {
            // Check existence
            $exists = false;
            if ($data['national_id']) {
                $exists = DB::table('players')->where('national_id', $data['national_id'])->exists();
            } else {
                $exists = DB::table('players')->where('name_ar', $data['name_ar'])->exists();
            }

            if ($exists) {
                // Update existing
                DB::table('players')
                    ->where($data['national_id'] ? 'national_id' : 'name_ar', $data['national_id'] ?: $data['name_ar'])
                    ->update([
                        'sport' => $data['sport'],
                        'gender' => $data['gender'],
                        'profile_role' => 'PLAYER',
                        'contract_fees' => $data['contract_fees'],
                        'contract_fees_type' => $data['contract_fees_type'],
                        'updated_at' => now(),
                    ]);
                continue;
            }

            // Insert new
            $nationalityAr = $data['nationality_ar'];
            $nationalityEn = $nationalities[$nationalityAr] ?? 'Kuwaiti';

            $contractStatus = 'ACTIVE';
            if ($data['contract_end_date']) {
                if (Carbon::parse($data['contract_end_date'])->isPast()) {
                    $contractStatus = 'EXPIRED';
                }
            }

            DB::table('players')->insert([
                'name_ar' => $data['name_ar'],
                'name' => $data['name_ar'], // Using same name for EN for now
                'national_id' => $data['national_id'],
                'date_of_birth' => $data['date_of_birth'],
                'nationality_ar' => $nationalityAr,
                'nationality' => $nationalityEn,
                'sport' => $data['sport'],
                'position' => 'Other', // Required field
                'contract_duration' => $data['contract_duration'],
                'contract_start_date' => $data['contract_start_date'],
                'contract_end_date' => $data['contract_end_date'],
                'contract_fees' => $data['contract_fees'],
                'contract_fees_type' => $data['contract_fees_type'],
                'contract_status' => $contractStatus,
                'profile_role' => 'PLAYER',
                'gender' => $data['gender'],
                'is_visible' => true,
                'visibility_settings' => json_encode([
                    'nationality' => true,
                    'age' => true,
                    'dateOfBirth' => true,
                    'position' => true,
                    'club' => true,
                    'marketValue' => true,
                    'preferredFoot' => true,
                    'height' => true,
                    'weight' => true,
                    'jerseyNumber' => true,
                    'previousClubs' => true,
                    'dealStatus' => true,
                    'contractInfo' => true,
                    'photos' => true,
                    'achievements' => true,
                    'stats' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
