<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Player;
use App\Models\Club;
use Illuminate\Support\Facades\DB;

class ClubMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Collect unique legacy club names from players
        $legacyClubs = DB::table('players')
            ->select('club_name_legacy', 'club_name_ar_legacy')
            ->whereNotNull('club_name_legacy')
            ->distinct()
            ->get();

        $this->command->info("Found " . $legacyClubs->count() . " unique legacy club names.");

        foreach ($legacyClubs as $legacy) {
            $nameEn = trim($legacy->club_name_legacy);
            $nameAr = trim($legacy->club_name_ar_legacy);

            if (empty($nameEn)) continue;

            // 2. Find or create the Club record
            $club = Club::updateOrCreate(
                ['name' => $nameEn],
                ['name_ar' => $nameAr]
            );

            // 3. Update players who match this legacy name and don't have a club_id yet
            $updateCount = Player::where('club_name_legacy', $nameEn)
                ->whereNull('club_id')
                ->update(['club_id' => $club->id]);

            if ($updateCount > 0) {
                $this->command->info("Linked {$updateCount} players to club: {$nameEn}");
            }
        }

        $this->command->info("Club migration completed successfully.");
    }
}
