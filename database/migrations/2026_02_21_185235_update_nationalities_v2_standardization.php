<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix German (Arabic spelling with Hamza)
        \App\Models\Player::where('nationality', 'German')
            ->orWhere('nationality_ar', 'الماني')
            ->update([
                'nationality' => 'German',
                'nationality_ar' => 'ألماني'
            ]);

        // 2. Fix Moroccan (Arabic-in-English-field)
        \App\Models\Player::where('nationality', 'مغربي')
            ->orWhere('nationality_ar', 'مغربي')
            ->update([
                'nationality' => 'Moroccan',
                'nationality_ar' => 'مغربي'
            ]);

        // 3. Fix Omani (Arabic-in-English-field)
        \App\Models\Player::where('nationality', 'عماني')
            ->orWhere('nationality_ar', 'عماني')
            ->update([
                'nationality' => 'Omani',
                'nationality_ar' => 'عماني'
            ]);

        // 4. Double check any others that might have Arabic in English field
        \App\Models\Player::where('nationality', 'أردني')->update(['nationality' => 'Jordanian']);
        \App\Models\Player::where('nationality', 'ألماني')->update(['nationality' => 'German']);
        \App\Models\Player::where('nationality', 'غير محدد')->update(['nationality' => 'Undefined']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
