<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;
use Illuminate\Support\Facades\Http;

class TranslateExistingClubs extends Command
{
    protected $signature = 'translate:clubs';
    protected $description = 'Translate existing player clubs and previous clubs to Arabic/English.';

    public function handle()
    {
        $players = Player::all();
        $count = 0;
        $this->info("Starting SMART translation for " . $players->count() . " players...");

        foreach ($players as $player) {
            $updated = false;

            // 1. Club
            if (!empty($player->club)) {
                if ($this->isArabic($player->club)) {
                    if (empty($player->club_ar)) $player->club_ar = $player->club;
                    $player->club = $this->translateTo($player->club, 'en');
                    $updated = true;
                } elseif (empty($player->club_ar)) {
                    $player->club_ar = $this->translateTo($player->club, 'ar');
                    $updated = true;
                }
            } elseif (!empty($player->club_ar)) {
                $player->club = $this->translateTo($player->club_ar, 'en');
                $updated = true;
            }

            // 2. Previous Clubs
            if (!empty($player->previous_clubs) && is_array($player->previous_clubs)) {
                $hasAr = false;
                foreach ($player->previous_clubs as $item) { if ($this->isArabic($item)) { $hasAr = true; break; } }
                if ($hasAr) {
                    if (empty($player->previous_clubs_ar)) $player->previous_clubs_ar = $player->previous_clubs;
                    $enClubs = [];
                    foreach ($player->previous_clubs as $item) $enClubs[] = $this->isArabic($item) ? $this->translateTo($item, 'en') : $item;
                    $player->previous_clubs = $enClubs;
                    $updated = true;
                } elseif (empty($player->previous_clubs_ar)) {
                    $arClubs = [];
                    foreach ($player->previous_clubs as $item) $arClubs[] = $this->translateTo($item, 'ar');
                    $player->previous_clubs_ar = $arClubs;
                    $updated = true;
                }
            } elseif (!empty($player->previous_clubs_ar)) {
                $enClubs = [];
                foreach ($player->previous_clubs_ar as $item) $enClubs[] = $this->translateTo($item, 'en');
                $player->previous_clubs = $enClubs;
                $updated = true;
            }

            // 3. Achievements
            if (!empty($player->achievements) && is_array($player->achievements)) {
                $hasAr = false;
                foreach ($player->achievements as $item) { if ($this->isArabic($item)) { $hasAr = true; break; } }
                if ($hasAr) {
                    if (empty($player->achievements_ar)) $player->achievements_ar = $player->achievements;
                    $enArr = [];
                    foreach ($player->achievements as $item) $enArr[] = $this->isArabic($item) ? $this->translateTo($item, 'en') : $item;
                    $player->achievements = $enArr;
                    $updated = true;
                } elseif (empty($player->achievements_ar)) {
                    $arArr = [];
                    foreach ($player->achievements as $item) $arArr[] = $this->translateTo($item, 'ar');
                    $player->achievements_ar = $arArr;
                    $updated = true;
                }
            }

            if ($updated) {
                $player->save();
                $count++;
                $this->info("Updated: {$player->name}");
            }
        }
        $this->info("Complete. Updated {$count} players.");
    }

    private function isArabic($text) { return !empty($text) && preg_match('/[\x{0600}-\x{06FF}]/u', $text); }

    private function translateTo($text, $target) {
        if (empty($text)) return null;
        $source = $target === 'en' ? 'ar' : 'en';
        try {
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$source}&tl={$target}&dt=t&q=" . urlencode($text);
            $response = Http::get($url);
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data[0][0][0])) {
                    $translated = '';
                    foreach ($data[0] as $segment) { if (isset($segment[0])) $translated .= $segment[0]; }
                    return $translated;
                }
            }
        } catch (\Exception $e) {}
        return $text;
    }
}
