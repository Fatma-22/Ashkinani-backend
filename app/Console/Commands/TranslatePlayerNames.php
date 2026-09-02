<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;
use Illuminate\Support\Facades\Http;

class TranslatePlayerNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:translate-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate player names from Arabic to English using a free Google Translate API endpoint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $players = Player::all();
        $count = 0;
        $total = $players->count();
        $this->info("Found {$total} players to process.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($players as $player) {
            // Check if name is Arabic and name_ar is empty
            if ($player->name && !$player->name_ar && preg_match('/\p{Arabic}/u', $player->name)) {
                $player->name_ar = $player->name;
                $englishName = $this->translateText($player->name, 'ar', 'en');
                $player->name = $englishName;
                $player->save();
                $count++;
            }
            // Or if name is empty but name_ar has value
            else if (!$player->name && $player->name_ar) {
                $englishName = $this->translateText($player->name_ar, 'ar', 'en');
                $player->name = $englishName;
                $player->save();
                $count++;
            }
            // Or if name is exactly the same as name_ar and it contains Arabic characters
            else if ($player->name === $player->name_ar && preg_match('/\p{Arabic}/u', $player->name)) {
                $englishName = $this->translateText($player->name_ar, 'ar', 'en');
                $player->name = $englishName;
                $player->save();
                $count++;
            }
            // Add a slight delay to avoid hitting rate limits
            usleep(200000); // 200 ms
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Translated {$count} player names from Arabic to English.");
    }

    /**
     * Translate text using a free Google Translate API alternative.
     */
    private function translateText($text, $source = 'ar', $target = 'en')
    {
        if (empty($text)) {
            return $text;
        }

        try {
            // Using Google Translate free endpoint
            $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$source}&tl={$target}&dt=t&q=" . urlencode($text);
            $response = file_get_contents($url);

            if ($response) {
                $data = json_decode($response, true);
                return $data[0][0][0] ?? $text;
            }

            return $text;
        } catch (\Exception $e) {
            $this->warn("Failed to translate: {$text} - Error: " . $e->getMessage());
            return $text; // Return original on failure
        }
    }
}
