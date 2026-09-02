<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Make all non-nullable columns in the players table nullable.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('nationality')->nullable()->change();
            $table->string('sport')->nullable()->change();
            $table->string('position')->nullable()->change();
            $table->decimal('market_value', 15, 2)->nullable()->default(null)->change();
            $table->json('visibility_settings')->nullable()->change();
        });

        // Handle enum columns separately using raw SQL (doctrine/dbal doesn't support enum ->change())
        DB::statement("ALTER TABLE players MODIFY COLUMN `date_of_birth` VARCHAR(255) NULL");
        DB::statement("ALTER TABLE players MODIFY COLUMN `gender` ENUM('MALE','FEMALE') NULL DEFAULT NULL");
        DB::statement("ALTER TABLE players MODIFY COLUMN `deal_status` VARCHAR(255) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE players MODIFY COLUMN `contract_fees_type` ENUM('FIXED','PERCENTAGE') NULL DEFAULT NULL");
        DB::statement("ALTER TABLE players MODIFY COLUMN `profile_role` ENUM('PLAYER','COACH') NULL DEFAULT 'PLAYER'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('nationality')->nullable(false)->change();
            $table->string('sport')->nullable(false)->change();
            $table->string('position')->nullable(false)->change();
            $table->decimal('market_value', 15, 2)->nullable(false)->default(0)->change();
            $table->json('visibility_settings')->nullable(false)->change();
        });

        DB::statement("ALTER TABLE players MODIFY COLUMN `date_of_birth` DATE NOT NULL");
        DB::statement("ALTER TABLE players MODIFY COLUMN `gender` ENUM('MALE','FEMALE') NOT NULL DEFAULT 'MALE'");
        DB::statement("ALTER TABLE players MODIFY COLUMN `deal_status` ENUM('SIGNED','FREE_AGENT','TRANSFER_LISTED','NEGOTIATION') NOT NULL DEFAULT 'FREE_AGENT'");
        DB::statement("ALTER TABLE players MODIFY COLUMN `contract_fees_type` ENUM('FIXED','PERCENTAGE') NOT NULL DEFAULT 'FIXED'");
        DB::statement("ALTER TABLE players MODIFY COLUMN `profile_role` ENUM('PLAYER','COACH') NOT NULL DEFAULT 'PLAYER'");
    }
};
