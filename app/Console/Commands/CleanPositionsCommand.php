<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;

class CleanPositionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:clean-positions {--dry-run : Only show changes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean and normalize player positions in the database';

    /**
     * Mapping for normalization
     */
    protected $mapping = [
        // Arabic
        'حارس مرمى' => 'GK',
        'قلب دفاع' => 'CB',
        'مدافع' => 'CB',
        'دفاع' => 'CB',
        'ظهير أيمن' => 'RB',
        'ظهير أيسر' => 'LB',
        'ظهير ايسر' => 'LB',
        'وسط مدافع' => 'CDM',
        'خط وسط دفاع' => 'CDM',
        'خط وسط' => 'CM',
        'لاعب وسط' => 'CM',
        'صانع ألعاب' => 'CAM',
        'خط وسط مهاجم' => 'CAM',
        'جناح أيمن' => 'RW',
        'جناح أيسر' => 'LW',
        'جناح' => 'RW',
        'رأس حربة' => 'ST',
        'مهاجم' => 'ST',
        'مهاجم ثاني' => 'SS',
        'محور' => 'P',
        'سنتر' => 'C',
        'ليبرو' => 'L',
        'ظهير' => 'RB',
        'عشاري' => 'DECATHLON',
        'جري' => 'SPRINT',
        'سباحة' => 'FREE',
        'صدر' => 'BREAST',
        'ظهر' => 'BACK',
        'فراشة' => 'FLY',
        'وثب' => 'JUMP',
        'رمي' => 'THROW',
        
        // English
        'Goalkeeper' => 'GK',
        'Defender' => 'CB',
        'Midfielder' => 'CM',
        'Forward' => 'ST',
        'Striker' => 'ST',
        'Center' => 'C',
        'Left winger' => 'LW',
        'Left Wing' => 'LW',
        'DM' => 'CDM',
        'ZM' => 'CM',
        'CF' => 'CF',
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $players = Player::whereNotNull('positions')->get();
        $totalChanged = 0;

        foreach ($players as $player) {
            /** @var \App\Models\Player $player */
            $originalPositions = $player->positions;
            
            // If it's a string (old format), convert to array
            if (is_string($originalPositions)) {
                $posArray = [$originalPositions];
            } else {
                $posArray = (array) $originalPositions;
            }

            $newPositions = [];

            foreach ($posArray as $pos) {
                // Handle combined positions like "CB+RB"
                $parts = preg_split('/[+\/]/', $pos);
                foreach ($parts as $part) {
                    $normalized = $this->normalize(trim($part));
                    if ($normalized && !in_array($normalized, $newPositions)) {
                        $newPositions[] = $normalized;
                    }
                }
            }

            // Sort and compare
            sort($newPositions);
            $currentSorted = (array) $originalPositions;
            sort($currentSorted);

            if ($newPositions !== $currentSorted) {
                $totalChanged++;
                $this->info("Player ID: {$player->id} | Name: {$player->name}");
                $this->line("  Original: " . json_encode($originalPositions, JSON_UNESCAPED_UNICODE));
                $this->line("  Normalized: " . json_encode($newPositions));

                if (!$dryRun) {
                    $player->positions = $newPositions;
                    $player->save();
                }
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Total players checked: " . $players->count());
        $this->info("Total players modified: " . $totalChanged);
        
        if ($dryRun) {
            $this->warn("DRY RUN: No changes were saved to the database.");
        } else {
            $this->info("Database updated successfully.");
        }
    }

    protected function normalize($pos)
    {
        if (empty($pos)) return null;

        // If it's already a standard code, return it
        $codes = [
            // Football
            'GK', 'CB', 'LCB', 'RCB', 'RB', 'LB', 'RWB', 'LWB', 
            'CDM', 'LDM', 'RDM', 'CM', 'LCM', 'RCM', 'CAM', 'LCAM', 'RCAM', 
            'RM', 'LM', 'RW', 'LW', 'CF', 'ST', 'SS', 'LS', 'RS',
            // Basketball
            'PG', 'SG', 'SF', 'PF', 'C',
            // Volleyball
            'S', 'OH', 'OPP', 'MB', 'L',
            // Handball
            'LW', 'CB', 'RW', 'P',
            // Futsal/Beach Soccer
            'FIXO', 'ALA', 'PIVOT',
            // Water Polo
            'WINGS', 'FLATS',
            // Cricket
            'BATSMAN', 'BOWLER', 'WK', 'ALL_ROUNDER',
            // Rugby
            'PROP', 'HOOKER', 'LOCK', 'FLANKER', 'SH', 'FH', 'CENTER', 'WING', 'FB', 'SECOND_ROW', 'SO',
            // American Football
            'QB', 'AM_RB', 'WR', 'TE', 'OL', 'DL', 'AM_LB', 'DB', 'K',
            // Baseball / Softball
            'PITCHER', 'CATCHER', '1B', '2B', 'BASE_SS', '3B', 'OF',
            // Hockey / Lacrosse
            'DEFENSE', 'WINGER', 'ATTACK', 'MIDFIELDER', 'FORWARD',
            // Paintball
            'FRONT', 'MID', 'BACK', 'SNAKE', 'DORITO',
            // Racket
            'SINGLE', 'DOUBLE', 'NET', 'RIGHT', 'LEFT',
            // Combat
            'STRIKER', 'GRAPPLER', 'KATA', 'KUMITE', 'GI', 'NO_GI', 'FOIL', 'EPEE', 'SABRE', 'FIGHTER',
            // Water / Racing
            'FREE', 'BREAST', 'FLY', 'MEDLEY', 'SPRINGBOARD', 'PLATFORM', 'STROKE', 'BOW', 'COX', 'HELM', 'TRIM', 'SHORTBOARD', 'LONGBOARD', 'RACER', 'FREESTYLE', 'DRIVER', 'CO_DRIVER',
            // Athletics / Gymnastics
            'SPRINT', 'MIDDLE', 'LONG', 'HURDLE', 'JUMP', 'THROW', 'DECATHLON', 'MARATHON', 'VAULT', 'POMMEL', 'RINGS', 'BARS', 'BEAM', 'FLOOR', 'SNATCH', 'CLEAN_JERK',
            // Target / Esports / Traditional
            'PISTOL', 'RIFLE', 'TRAP', 'RECURVE', 'COMPOUND', 'PLAYER', 'TANK', 'JUNGLE', 'AWP', 'ENTRY', 'IGL', 'LURKER', 'JOCKEY', 'FALCONER', 'ATHLETE'
        ];

        if (in_array(strtoupper($pos), $codes)) {
            return strtoupper($pos);
        }

        // Check mapping
        if (isset($this->mapping[$pos])) {
            return $this->mapping[$pos];
        }

        if (isset($this->mapping[strtoupper($pos)])) {
            return $this->mapping[strtoupper($pos)];
        }

        return $pos; // Return as is if no mapping found, to avoid losing data
    }
}
