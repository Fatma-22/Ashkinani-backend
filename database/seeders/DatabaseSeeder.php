<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Owner
        User::create([
            'name' => 'Ashkanani Owner',
            'email' => 'owner@ashkanani.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        // 2. Create Admins
        $adminNames = [
            ['en' => 'System Administrator', 'ar' => 'مدير النظام'],
            ['en' => 'Ahmed Mansour', 'ar' => 'أحمد منصور'],
        ];

        $defaultPermissions = [
            'canAddPlayers' => true,
            'canEditPlayers' => true,
            'canDeletePlayers' => true,
            'canAddAgents' => true,
            'canEditAgents' => true,
            'canDeleteAgents' => true,
            'canViewReports' => true,
            'canViewFinancials' => true,
            'canManageMembers' => true,
        ];

        foreach ($adminNames as $index => $adminData) {
            $user = User::create([
                'name' => $adminData['en'],
                'email' => "admin" . ($index + 1) . "@ashkanani.com",
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'ADMIN',
                'is_active' => true,
            ]);

            \App\Models\Admin::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '965123456' . $index,
                'permissions' => $defaultPermissions,
                'is_active' => true,
                'created_by' => 1
            ]);
        }

        // 3. Create Agents
        $agentNames = [
            ['en' => 'Jorge Mendes', 'ar' => 'خورخي مينديز', 'agency' => 'Gestifute'],
            ['en' => 'Mino Raiola', 'ar' => 'مينو رايولا', 'agency' => 'Team Raiola'],
            ['en' => 'Jonathan Barnett', 'ar' => 'جوناثان بارنيت', 'agency' => 'Stellar Group'],
            ['en' => 'Pini Zahavi', 'ar' => 'بيني زاهافي', 'agency' => 'Gol International'],
            ['en' => 'Kuwaiti Agent', 'ar' => 'وكيل كويتي', 'agency' => 'Kuwait Sports Agency'],
        ];

        $agents = [];
        foreach ($agentNames as $index => $agentData) {
            $user = User::create([
                'name' => $agentData['en'],
                'email' => "agent" . ($index + 1) . "@ashkanani.com",
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'AGENT',
                'is_active' => true,
            ]);

            $agents[] = \App\Models\Agent::create([
                'user_id' => $user->id,
                'name' => $agentData['en'],
                'name_ar' => $agentData['ar'],
                'email' => $user->email,
                'phone' => '9659000000' . $index,
                'company' => $agentData['agency'],
            ]);
        }

        // 4. Create Employees
        $employeeSpecs = [
            ['name' => 'Sara Al-Fadli', 'ar' => 'سارة الفضلي', 'pos' => 'Accountant', 'pos_ar' => 'محاسبة', 'dept' => 'Finance'],
            ['name' => 'Mishari Al-Kandari', 'ar' => 'مشاري الكندري', 'pos' => 'Marketing Manager', 'pos_ar' => 'مدير تسويق', 'dept' => 'Marketing'],
            ['name' => 'Laila Al-Otaibi', 'ar' => 'ليلى العتيبي', 'pos' => 'Human Resources', 'pos_ar' => 'موارد بشرية', 'dept' => 'HR'],
        ];

        foreach ($employeeSpecs as $spec) {
            \App\Models\Employee::create([
                'name' => $spec['name'],
                'name_ar' => $spec['ar'],
                'position' => $spec['pos'],
                'position_ar' => $spec['pos_ar'],
                'department' => $spec['dept'],
                'department_ar' => $spec['dept'], // Simplified
                'salary' => rand(1500, 3500),
                'hire_date' => now()->subMonths(rand(6, 24)),
                'phone' => '965' . rand(50000000, 99999999),
                'email' => strtolower(str_replace(' ', '.', $spec['name'])) . "@ashkanani.com",
                'is_active' => true,
            ]);
        }

        // 5. Create Players
        $playerNames = [
            ['en' => 'Bader Al-Mutawa', 'ar' => 'بدر المطوع'],
            ['en' => 'Fahad Al-Enezi', 'ar' => 'فهد العنزي'],
            ['en' => 'Yousef Nasser', 'ar' => 'يوسف ناصر'],
            ['en' => 'Faisal Zayed', 'ar' => 'فيصل زايد'],
            ['en' => 'Redha Hani', 'ar' => 'رضا هاني'],
            ['en' => 'Shabaib Al-Khaldi', 'ar' => 'شبيب الخالدي'],
            ['en' => 'Eid Al-Rashidi', 'ar' => 'عيد الرشيدي'],
            ['en' => 'Hamad Al-Harbi', 'ar' => 'حمد الحربي'],
            ['en' => 'Khaled Al-Rashidi', 'ar' => 'خالد الرشيدي'],
            ['en' => 'Sulaiman Abdulghafoor', 'ar' => 'سليمان عبدالغفور'],
            ['en' => 'Ahmad Al-Dhefiri', 'ar' => 'أحمد الظفيري'],
            ['en' => 'Abdullah Al-Buloushi', 'ar' => 'عبدالله البلوشي'],
            ['en' => 'Mubarak Al-Faneeni', 'ar' => 'مبارك الفنيني'],
            ['en' => 'Mobarak Al-Otaibi', 'ar' => 'مبارك العتيبي'],
            ['en' => 'Ali Khalaf', 'ar' => 'علي خلف'],
            ['en' => 'Bandar Al-Salama', 'ar' => 'بندر السلامة'],
            ['en' => 'Fawaz Al-Otaibi', 'ar' => 'فواز العتيبي'],
            ['en' => 'Mahdi Dashti', 'ar' => 'مهدي دشتي'],
            ['en' => 'Mohammad Al-Hwaidi', 'ar' => 'محمد الهويدي'],
            ['en' => 'Sultan Al-Enezi', 'ar' => 'سلطان العنزي'],
        ];

        $sports = ['Football', 'Basketball'];
        $positions = ['Forward', 'Midfielder', 'Defender', 'Goalkeeper'];
        $clubs = [
            ['en' => 'Qadsia SC', 'ar' => 'نادي القادسية'],
            ['en' => 'Kuwait SC', 'ar' => 'نادي الكويت'],
            ['en' => 'Al Arabi SC', 'ar' => 'نادي العربي'],
            ['en' => 'Kazma SC', 'ar' => 'نادي كاظمة'],
            ['en' => 'Salmiya SC', 'ar' => 'نادي السالمية'],
        ];
        
        // Diverse nationalities for testing
        $nationalities = [
            ['en' => 'Kuwaiti', 'ar' => 'كويتي'],
            ['en' => 'Saudi', 'ar' => 'سعودي'],
            ['en' => 'Egyptian', 'ar' => 'مصري'],
            ['en' => 'Brazilian', 'ar' => 'برازيلي'],
            ['en' => 'French', 'ar' => 'فرنسي'],
            ['en' => 'Spanish', 'ar' => 'إسباني'],
            ['en' => 'English', 'ar' => 'إنجليزي'],
            ['en' => 'German', 'ar' => 'ألماني'],
            ['en' => 'Argentine', 'ar' => 'أرجنتيني'],
            ['en' => 'Emirati', 'ar' => 'إماراتي'],
        ];

        $players = [];
        foreach ($playerNames as $index => $pData) {
            $club = $clubs[array_rand($clubs)];
            $nationality = $nationalities[$index % count($nationalities)];
            $players[] = \App\Models\Player::create([
                'name' => $pData['en'],
                'name_ar' => $pData['ar'],
                'nationality' => $nationality['en'],
                'nationality_ar' => $nationality['ar'],
                'date_of_birth' => now()->subYears(rand(18, 32))->format('Y-m-d'),
                'sport' => $sports[rand(0, 100) > 80 ? 1 : 0], // Mostly football
                'position' => $positions[array_rand($positions)],
                'club' => $club['en'],
                'club_ar' => $club['ar'],
                'market_value' => rand(500000, 15000000),
                'agent_id' => $agents[array_rand($agents)]->id,
                'is_visible' => true,
                'deal_status' => 'SIGNED',
                'visibility_settings' => [
                    'nationality' => true,
                    'age' => true,
                    'dateOfBirth' => false,
                    'position' => true,
                    'club' => true,
                    'marketValue' => true,
                    'preferredFoot' => true,
                    'height' => true,
                    'weight' => true,
                    'previousClubs' => true,
                    'dealStatus' => true,
                    'contractInfo' => false,
                    'photos' => true,
                    'achievements' => true,
                    'stats' => true,
                ],
            ]);
        }

        // 6. Create Contracts
        foreach ($players as $player) {
            \App\Models\Contract::create([
                'player_id' => $player->id,
                'agent_id' => $player->agent_id,
                'type' => 'PROFESSIONAL',
                'status' => 'ACTIVE',
                'start_date' => now()->subMonths(rand(1, 12)),
                'end_date' => now()->addMonths(rand(12, 48)),
                'annual_salary' => rand(200000, 1000000),
                'currency' => 'KWD',
                'is_visible' => true,
            ]);
        }

        // 7. Create Financial Records (Recent)
        for ($i = 0; $i < 30; $i++) {
            \App\Models\FinancialRecord::create([
                'type' => (rand(0, 100) > 30) ? 'INCOME' : 'EXPENSE',
                'category' => ['Commission', 'Salary', 'Marketing', 'Consultation'][rand(0, 3)],
                'amount' => rand(1000, 50000),
                'currency' => 'KWD',
                'description' => "Financial transaction record #" . ($i + 1),
                'transaction_date' => now()->subDays(rand(0, 90)),
                'created_by' => 1,
            ]);
        }
    }
}
