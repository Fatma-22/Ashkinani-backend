<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->enum('type', ['SPONSOR', 'PARTNER'])->default('SPONSOR')->after('id');
            $table->date('start_date')->nullable()->after('type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('agreement_text')->nullable()->after('end_date');
            $table->text('agreement_text_ar')->nullable()->after('agreement_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn(['type', 'start_date', 'end_date', 'agreement_text', 'agreement_text_ar']);
        });
    }
};
