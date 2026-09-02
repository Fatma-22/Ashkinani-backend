<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ids = [
            'CD176459',
            'U057738',
            'R308200',
            'N01766687',
            'A03167857',
            'PB3448654',
            'S004847',
            'PP0254883',
            'OP0721768',
            '22AK27417',
            '24AT10535',
            'H259782',
            'AAG964815',
            '1141700862',
            'Z249822',
            'AV78410'
        ];

        DB::table('players')
            ->whereIn('national_id', $ids)
            ->update(['contract_nature' => 'AUTHORIZATION']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $ids = [
            'CD176459',
            'U057738',
            'R308200',
            'N01766687',
            'A03167857',
            'PB3448654',
            'S004847',
            'PP0254883',
            'OP0721768',
            '22AK27417',
            '24AT10535',
            'H259782',
            'AAG964815',
            '1141700862',
            'Z249822',
            'AV78410'
        ];

        DB::table('players')
            ->whereIn('national_id', $ids)
            ->update(['contract_nature' => 'SIGNING']);
    }
};
