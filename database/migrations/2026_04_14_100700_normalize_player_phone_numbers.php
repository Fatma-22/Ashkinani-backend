<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration cleans up existing phone numbers in the database
     * by removing spaces, dashes, and parentheses to ensure consistency
     * with the new normalization logic.
     */
    public function up(): void
    {
        // 1. Normalize phone numbers in the 'players' table
        DB::table('players')->whereNotNull('phone')->chunkById(100, function ($players) {
            foreach ($players as $player) {
                // Remove spaces, dashes, and parentheses
                $normalized = preg_replace('/[\s\-()]/', '', $player->phone);
                
                if ($normalized !== $player->phone) {
                    DB::table('players')
                        ->where('id', $player->id)
                        ->update(['phone' => $normalized]);
                }
            }
        });

        // 2. Normalize phone numbers in the 'users' table
        DB::table('users')->whereNotNull('phone')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                // Remove spaces, dashes, and parentheses
                $normalized = preg_replace('/[\s\-()]/', '', $user->phone);
                
                if ($normalized !== $user->phone) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['phone' => $normalized]);
                    
                    // Optional: If national_id also needs cleaning
                    if ($user->national_id) {
                        $cleanNid = trim($user->national_id);
                        if ($cleanNid !== $user->national_id) {
                            DB::table('users')
                                ->where('id', $user->id)
                                ->update(['national_id' => $cleanNid]);
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is data-destructive in terms of formatting.
        // Reverting it would require a backup or complex tracking of original formats.
    }
};
