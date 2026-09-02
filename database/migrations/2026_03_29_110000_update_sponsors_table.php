<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->text('services_en')->nullable()->after('name_ar');
            $table->text('services_ar')->nullable()->after('services_en');
            $table->string('contract_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn(['services_en', 'services_ar', 'contract_path']);
        });
    }
};
