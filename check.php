<?php
$p = App\Models\Player::where('name_ar', 'like', '%خالد صالح نمر غاطي%')->first();
if($p) {
    echo "ID: " . $p->id . "\n";
    echo "Dates in DB: Start: " . $p->contract_start_date . ", End: " . $p->contract_end_date . ", Status: " . $p->contract_status . "\n";
    echo "Docs count: " . $p->documents()->where('type', 'contract')->count() . "\n";
} else {
    echo "Player not found\n";
}
