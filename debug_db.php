<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use App\Models\Agent;
use App\Models\Player;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Debugging User/Agent Relationships ---\n";

$agents = Agent::all();
echo "Total Agents in DB: " . $agents->count() . "\n";

foreach ($agents as $agent) {
    echo "Agent ID: {$agent->id}\n";
    echo "  - Name: {$agent->name}\n";
    echo "  - User ID: " . ($agent->user_id ?? 'NULL') . "\n";

    $user = $agent->user;
    if ($user) {
        echo "  - Linked User: {$user->email} (ID: {$user->id}, Role: {$user->role})\n";
    } else {
        echo "  - Linked User: NOT FOUND\n";
    }

    $playersCount = $agent->players()->count();
    echo "  - Players Count: {$playersCount}\n";

    foreach ($agent->players as $player) {
        echo "    * Player: {$player->name} (Market Value: {$player->market_value}, End Date: " . ($player->contract_end_date ?? 'N/A') . ")\n";
    }
}

echo "\n--- Agents with No Users ---\n";
$orphanAgents = Agent::whereNull('user_id')->get();
echo "Count: " . $orphanAgents->count() . "\n";

echo "\n--- Users with Role AGENT and their links ---\n";
$agentUsers = User::where('role', 'AGENT')->get();
foreach ($agentUsers as $u) {
    echo "User: {$u->email} (ID: {$u->id})\n";
    echo "  - Has Agent Profile: " . ($u->agent ? 'YES (ID: ' . $u->agent->id . ')' : 'NO') . "\n";
}
