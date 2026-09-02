<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add the new `positions` JSON column
        Schema::table('players', function (Blueprint $table) {
            $table->json('positions')->nullable()->after('sport');
        });

        // 2. Data Migration: convert old `position` string to the new `positions` array
        $players = DB::table('players')->whereNotNull('position')->get();

        foreach ($players as $player) {
            $str = mb_strtolower(trim($player->position));
            $sport = strtoupper(trim($player->sport ?? 'FOOTBALL'));
            $newPositions = [];

            if ($sport === 'BASKETBALL') {
                if (preg_match('/pg|صانع/u', $str)) $newPositions[] = 'PG';
                if (preg_match('/sg|مدافع|مسدد|رامي/u', $str)) $newPositions[] = 'SG';
                if (preg_match('/sf|هجوم صغير|سمول/u', $str)) $newPositions[] = 'SF';
                if (preg_match('/pf|هجوم قوي|باور/u', $str)) $newPositions[] = 'PF';
                if (preg_match('/c|center|سنتر/u', $str)) $newPositions[] = 'C';
            } elseif ($sport === 'VOLLEYBALL') {
                if (preg_match('/s|setter|معد|معّد/u', $str)) $newPositions[] = 'S';
                if (preg_match('/oh|خارجي/u', $str)) $newPositions[] = 'OH';
                if (preg_match('/opp|معاكس/u', $str)) $newPositions[] = 'OPP';
                if (preg_match('/mb|أوسط/u', $str)) $newPositions[] = 'MB';
                if (preg_match('/l|libero|ليبرو|مدافع حر/u', $str)) $newPositions[] = 'L';
            } elseif ($sport === 'HANDBALL') {
                if (preg_match('/gk|حارس/u', $str)) $newPositions[] = 'GK';
                if (preg_match('/cb|صانع/u', $str)) $newPositions[] = 'CB';
                if (preg_match('/lb|ظهير أيسر|ايسر/u', $str)) $newPositions[] = 'LB';
                if (preg_match('/rb|ظهير أيمن|ايمن/u', $str)) $newPositions[] = 'RB';
                if (preg_match('/lw|جناح أيسر|ايسر/u', $str)) $newPositions[] = 'LW';
                if (preg_match('/rw|جناح أيمن|ايمن/u', $str)) $newPositions[] = 'RW';
                if (preg_match('/p|محور|دائرة/u', $str)) $newPositions[] = 'P';
            } else {
                // Default to Football matching
                // Goalkeeper
                if (preg_match('/gk|حارس/u', $str)) $newPositions[] = 'GK';

                // Defenders
                if (preg_match('/cb|قلب|سنتر|سرد باك|مدافع(?![\s]*(حر|أيمن|أيسر|يمين|يسار))/u', $str)) $newPositions[] = 'CB';
                if (preg_match('/rb|\brwb\b|باك يمين|ظهير أيمن|ظهير ايمن|ظهير يمين|متقدم يمين|مدافع يمين/u', $str)) $newPositions[] = 'RB';
                if (preg_match('/lb|\blwb\b|باك يسار|ظهير أيسر|ظهير ايسر|ظهير يسار|باك ليفت|طرف يسار|متقدم يسار|مدافع يسار/u', $str)) $newPositions[] = 'LB';

                // Midfielders
                if (preg_match('/cdm|ارتكاز|محور\s*6|6\s*محور|وسط مدافع|محور ارتكاز/u', $str)) $newPositions[] = 'CDM';
                if (preg_match('/cm(?![\w])|خط وسط|وسط(?![\s]*(مدافع|مهاجم|أيسر|ايسر|أيمن|ايمن))|لاعب وسط|محور\s*8|8\s*محور/u', $str)) $newPositions[] = 'CM';
                if (preg_match('/محور(?![\s0-9]*(6|8|ارتكاز))/u', $str)) {
                    // "محور" alone is usually CDM in the Gulf or CM
                    $newPositions[] = 'CDM';
                    $newPositions[] = 'CM'; 
                }
                
                if (preg_match('/cam|صانع|وسط مهاجم|صانع ألعاب/u', $str)) $newPositions[] = 'CAM';
                if (preg_match('/rm|وسط أيمن|وسط ايمن/u', $str)) $newPositions[] = 'RM';
                if (preg_match('/lm|وسط أيسر|وسط ايسر/u', $str)) $newPositions[] = 'LM';

                // Attackers
                if (preg_match('/rw|جناح يمين|جناح أيمن|جناح ايمن|ايمين/u', $str)) $newPositions[] = 'RW';
                if (preg_match('/lw|جناح يسار|جناح أيسر|جناح ايسر/u', $str)) $newPositions[] = 'LW';
                if (preg_match('/جناح(?![\s]*(يمين|يسار|أيسر|ايسر|أيمن|ايمن|ايمين))/u', $str)) {
                    // If simply "جناح", normally they play both wings
                    $newPositions[] = 'RW';
                    $newPositions[] = 'LW';
                }

                if (preg_match('/st|cf|مهاجم(?![\s]*(ثاني|وهمي|.\/))/u', $str)) $newPositions[] = 'ST';
                if (preg_match('/ss|تحت مهاجم|مهاجم ثاني|تحت المهاجم/u', $str)) $newPositions[] = 'SS';

                // Roles mapped sometimes
                if (preg_match('/مساعد مدرب|coach/u', $str)) {
                    DB::table('players')->where('id', $player->id)->update(['profile_role' => 'COACH']);
                }
            }

            // Clean up and unique
            $newPositions = array_values(array_unique($newPositions));
            
            DB::table('players')->where('id', $player->id)->update([
                'positions' => json_encode($newPositions, JSON_UNESCAPED_UNICODE)
            ]);
        }

        // 3. Drop the old 'position' column
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('position')->nullable()->after('sport');
        });

        // Revert data
        $players = DB::table('players')->whereNotNull('positions')->get();
        foreach ($players as $player) {
            $positions = json_decode($player->positions, true) ?? [];
            DB::table('players')->where('id', $player->id)->update([
                'position' => !empty($positions) ? implode('/', $positions) : null
            ]);
        }

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('positions');
        });
    }
};
