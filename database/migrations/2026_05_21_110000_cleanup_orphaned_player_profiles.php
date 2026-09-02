<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Player;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Find all player profiles with user_id = null and a phone number
        $unlinkedPlayers = Player::whereNull('user_id')->whereNotNull('phone')->get();

        foreach ($unlinkedPlayers as $player) {
            $phone = preg_replace('/\D/', '', $player->phone);
            if (strlen($phone) < 8) {
                continue;
            }
            $suffix = substr($phone, -8);

            // 2. Find if there is an active user with the same phone suffix
            $activeUser = User::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '') LIKE ?", ['%' . $suffix])->first();

            if ($activeUser) {
                // If there's an active user, check if they already have their own player profile,
                // or if this orphaned profile's name/email is completely different.
                $hasOwnProfile = Player::where('user_id', $activeUser->id)->exists();
                
                $namesMatch = false;
                if ($player->name && $activeUser->name) {
                    $namesMatch = strtolower(trim($player->name)) === strtolower(trim($activeUser->name));
                }

                $emailsMatch = false;
                if ($player->email && $activeUser->email) {
                    $emailsMatch = strtolower(trim($player->email)) === strtolower(trim($activeUser->email));
                }

                // If the user already has a linked profile, OR if the names/emails do not match,
                // this unlinked profile is an orphan from a deleted user and should be safely deleted.
                if ($hasOwnProfile || (!$namesMatch && !$emailsMatch)) {
                    // This is an orphan profile! Clean up its files and delete it.
                    $this->deletePlayerFiles($player);
                    $player->delete();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way cleanup migration, no action needed to reverse
    }

    /**
     * Helper to safely delete player files from storage disk.
     */
    private function deletePlayerFiles($player): void
    {
        $files = [
            $player->cv_url,
            $player->club_logo,
            $player->volleyball_stats_pdf,
            $player->volleyball_ranking_image,
            $player->strategy_pdf
        ];

        foreach ($files as $file) {
            if ($file && !filter_var($file, FILTER_VALIDATE_URL) && !str_starts_with($file, 'data:')) {
                $cleaned = str_starts_with($file, 'storage/') ? substr($file, 8) : $file;
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleaned)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($cleaned);
                    }
                } catch (\Exception $e) {
                    \Log::error("Migration cleanup failed to delete file: " . $e->getMessage());
                }
            }
        }

        // Clean up photo relation files
        foreach ($player->photos as $photo) {
            if ($photo->url && !filter_var($photo->url, FILTER_VALIDATE_URL)) {
                $cleaned = str_starts_with($photo->url, 'storage/') ? substr($photo->url, 8) : $photo->url;
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleaned)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($cleaned);
                    }
                } catch (\Exception $e) {
                    \Log::error("Migration cleanup failed to delete photo: " . $e->getMessage());
                }
            }
        }

        // Clean up document relation files
        foreach ($player->documents as $doc) {
            if ($doc->url && !filter_var($doc->url, FILTER_VALIDATE_URL)) {
                $cleaned = str_starts_with($doc->url, 'storage/') ? substr($doc->url, 8) : $doc->url;
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleaned)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($cleaned);
                    }
                } catch (\Exception $e) {
                    \Log::error("Migration cleanup failed to delete document: " . $e->getMessage());
                }
            }
        }
    }
};
