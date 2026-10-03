<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateExpiredContractsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'contracts:update-expired
                            {--dry-run : Show how many would be updated without actually updating}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically update contract_status to EXPIRED for players whose contract_end_date has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now()->toDateString();
        $isDryRun = $this->option('dry-run');

        // Find players that should be marked EXPIRED:
        // 1. contract_end_date is set and in the past
        // 2. contract_status is NOT already EXPIRED
        // 3. Not overridden by an active Ashkanani agency document contract
        $query = Player::whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '<', $now)
            ->where('contract_status', '!=', 'EXPIRED')
            ->whereDoesntHave('documents', function ($c) use ($now) {
                $c->where('type', 'contract')
                  ->whereNotNull('end_date')
                  ->whereDate('end_date', '>=', $now);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('✅ No contracts need updating — everything is up to date.');
            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("🔍 Dry-run: {$count} player(s) would have their contract_status set to EXPIRED.");
            $query->select('id', 'name', 'contract_end_date', 'contract_status')
                  ->get()
                  ->each(fn($p) => $this->line("  • [{$p->id}] {$p->name} — end: {$p->contract_end_date}, status: {$p->contract_status}"));
            return self::SUCCESS;
        }

        // Perform the bulk update
        $updated = $query->update(['contract_status' => 'EXPIRED']);

        $this->info("✅ Updated {$updated} player contract(s) to EXPIRED.");

        Log::info("contracts:update-expired — marked {$updated} player(s) as EXPIRED (end_date < {$now}).");

        return self::SUCCESS;
    }
}
