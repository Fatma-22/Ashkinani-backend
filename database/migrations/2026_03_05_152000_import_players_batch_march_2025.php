<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        $players = [
            [
                'name' => 'MARICO ISMAILA BAMBA',
                'name_ar' => 'ماريكو إسمائيلا بامبا',
                'national_id' => 'A03167857',
                'position' => 'PLAYER',
                'nationality' => 'Senegalese',
                'nationality_ar' => 'سنغالي',
                'gender' => 'MALE',
                'contract_start_date' => '2025-06-21',
                'contract_end_date' => '2025-07-21',
                'contract_duration' => 1,
            ],
            [
                'name' => 'MURRAY JORDAN DAVID',
                'name_ar' => 'موراي جوردان ديفيد',
                'national_id' => 'PB3448654',
                'position' => 'PLAYER',
                'nationality' => 'Australian',
                'nationality_ar' => 'أسترالي',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-05',
                'contract_end_date' => '2025-07-26',
                'contract_duration' => 1,
            ],
            [
                'name' => 'SALEM MAHMOUD SULEIMAN',
                'name_ar' => 'سالم محمود سليمان',
                'national_id' => 'S004847',
                'position' => 'PLAYER',
                'nationality' => 'Jordanian',
                'nationality_ar' => 'أردني',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-06',
                'contract_end_date' => '2025-07-27',
                'contract_duration' => 1,
            ],
            [
                'name' => 'EUGENE CHOUCHOU',
                'name_ar' => 'يوجين شوشو',
                'national_id' => 'PP0254883',
                'position' => 'PLAYER',
                'nationality' => 'Liberian',
                'nationality_ar' => 'ليبيري',
                'gender' => 'MALE',
                'contract_start_date' => '2025-06-28',
                'contract_end_date' => '2025-07-28',
                'contract_duration' => 1,
            ],
            [
                'name' => 'ENOCK MOLIA LIHOZASIA',
                'name_ar' => 'إينوك موليا ليهوزاسيا',
                'national_id' => 'OP0721768',
                'position' => 'PLAYER',
                'nationality' => 'Congolese',
                'nationality_ar' => 'كونغولي',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-08',
                'contract_end_date' => '2025-08-08',
                'contract_duration' => 1,
            ],
            [
                'name' => 'OUMAR FAROUK KOMARA',
                'name_ar' => 'عمر فاروق كومارا',
                'national_id' => '22AK27417',
                'position' => 'PLAYER',
                'nationality' => 'Ivorian',
                'nationality_ar' => 'إيفواري',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-08',
                'contract_end_date' => '2025-08-08',
                'contract_duration' => 1,
            ],
            [
                'name' => 'ECUA ECUA CELESTIN',
                'name_ar' => 'إيكوا إيكوا سيليستان',
                'national_id' => '24AT10535',
                'position' => 'PLAYER',
                'nationality' => 'Ivorian',
                'nationality_ar' => 'إيفواري',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-08',
                'contract_end_date' => '2025-08-08',
                'contract_duration' => 1,
            ],
            [
                'name' => 'GHOUFRANE EL NAOUALI',
                'name_ar' => 'غفران النوالي',
                'national_id' => 'H259782',
                'position' => 'مدافع',
                'nationality' => 'Tunisian',
                'nationality_ar' => 'تونسي',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-11',
                'contract_end_date' => '2025-07-21',
                'contract_duration' => 1,
            ],
            [
                'name' => 'NICOLAS MORRO',
                'name_ar' => 'نيكولاس مورو',
                'national_id' => 'AAG964815',
                'position' => 'مدافع',
                'nationality' => 'Argentinian',
                'nationality_ar' => 'أرجنتيني',
                'gender' => 'MALE',
                'contract_start_date' => '2025-07-11',
                'contract_end_date' => '2025-07-21',
                'contract_duration' => 1,
            ],
            [
                'name' => 'مهند السبيعي',
                'name_ar' => 'مهند السبيعي',
                'national_id' => '1141700862',
                'position' => 'PLAYER',
                'nationality' => 'Saudi',
                'nationality_ar' => 'سعودي',
                'gender' => 'MALE',
                'contract_start_date' => '2025-08-15',
                'contract_end_date' => '2025-09-15',
                'contract_duration' => 1,
            ],
            [
                'name' => 'سعد نايف المطيري',
                'name_ar' => 'سعد نايف المطيري',
                'national_id' => 'Z249822',
                'position' => 'PLAYER',
                'nationality' => 'Saudi',
                'nationality_ar' => 'سعودي',
                'gender' => 'MALE',
                'contract_start_date' => '2025-08-24',
                'contract_end_date' => '2025-09-07',
                'contract_duration' => 1,
            ],
        ];

        foreach ($players as $data) {
            // Skip if already exists by national_id
            $exists = DB::table('players')
                ->where('national_id', $data['national_id'])
                ->exists();

            if ($exists) {
                DB::table('players')
                    ->where('national_id', $data['national_id'])
                    ->update([
                        'name' => $data['name'],
                        'name_ar' => $data['name_ar'],
                        'nationality' => $data['nationality'],
                        'nationality_ar' => $data['nationality_ar'],
                        'contract_start_date' => $data['contract_start_date'],
                        'contract_end_date' => $data['contract_end_date'],
                        'updated_at' => now(),
                    ]);
                continue;
            }

            $contractStatus = Carbon::parse($data['contract_end_date'])->isPast()
                ? 'EXPIRED'
                : 'ACTIVE';

            DB::table('players')->insert([
                'name' => $data['name'],
                'name_ar' => $data['name_ar'],
                'national_id' => $data['national_id'],
                'date_of_birth' => null,
                'nationality' => $data['nationality'],
                'nationality_ar' => $data['nationality_ar'],
                'sport' => 'Football',
                'position' => $data['position'] ?? 'Other',
                'gender' => $data['gender'],
                'profile_role' => 'PLAYER',
                'contract_start_date' => $data['contract_start_date'],
                'contract_end_date' => $data['contract_end_date'],
                'contract_duration' => $data['contract_duration'],
                'contract_fees' => 0,
                'contract_fees_type' => 'FIXED',
                'contract_status' => $contractStatus,
                'deal_status' => 'FREE_AGENT',
                'market_value' => 0,
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
        $nationalIds = [
            'A03167857',
            'PB3448654',
            'S004847',
            'PP0254883',
            'OP0721768',
            '22AK27417',
            '24AT10535',
            'H259782',
            'AAG964815',
            '1141700862',
            'Z249822',
        ];

        DB::table('players')
            ->whereIn('national_id', $nationalIds)
            ->delete();
    }
};
