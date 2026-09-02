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
        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'meeting_type')) {
                $table->string('meeting_type')->after('title')->nullable();
            }
            if (!Schema::hasColumn('meetings', 'duration')) {
                $table->string('duration')->after('meeting_type')->nullable();
            }
            if (!Schema::hasColumn('meetings', 'fees')) {
                $table->decimal('fees', 10, 2)->after('duration')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('meetings', 'meeting_type')) $cols[] = 'meeting_type';
            if (Schema::hasColumn('meetings', 'duration')) $cols[] = 'duration';
            if (Schema::hasColumn('meetings', 'fees')) $cols[] = 'fees';
            
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
