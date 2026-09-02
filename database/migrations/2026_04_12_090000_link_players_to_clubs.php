<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Club;
use App\Models\Player;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add club_id column to players table
        if (!Schema::hasColumn('players', 'club_id')) {
            Schema::table('players', function (Blueprint $table) {
                $table->foreignId('club_id')->nullable()->after('scout_id')->constrained('clubs')->nullOnDelete();
            });
        }

        // 2. Harvest unique club strings and create Club records
        $uniqueClubs = DB::table('players')
            ->select('club', 'club_ar', 'club_logo')
            ->whereNotNull('club')
            ->orWhereNotNull('club_ar')
            ->groupBy('club', 'club_ar', 'club_logo')
            ->get();

        foreach ($uniqueClubs as $c) {
            $nameEn = trim($c->club);
            $nameAr = trim($c->club_ar);
            
            if (empty($nameEn) && empty($nameAr)) continue;

            // Find or create the club
            // We search by name to avoid duplicates if the club already exists in clubs table
            $club = Club::where(function($query) use ($nameEn, $nameAr) {
                if ($nameEn) $query->where('name', $nameEn);
                if ($nameAr) $query->orWhere('name_ar', $nameAr);
            })->first();

            if (!$club) {
                $club = Club::create([
                    'name' => $nameEn ?: $nameAr, // Fallback if one is missing
                    'name_ar' => $nameAr ?: $nameEn,
                    'logo_path' => $c->club_logo // Migrate established logo path if available
                ]);
            }

            // 3. Update players with this club_id
            DB::table('players')
                ->where(function($query) use ($nameEn, $nameAr) {
                    if ($nameEn) $query->where('club', $nameEn);
                    if ($nameAr) $query->orWhere('club_ar', $nameAr);
                })
                ->update(['club_id' => $club->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });
    }
};
