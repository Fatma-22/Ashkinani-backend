<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $blueprint) {
            $blueprint->string('tier')->nullable()->after('type'); // Diamond, Gold, Silver, Partner
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $blueprint) {
            $blueprint->dropColumn('tier');
        });
    }
};
