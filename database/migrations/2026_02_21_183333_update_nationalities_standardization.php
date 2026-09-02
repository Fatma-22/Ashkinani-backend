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
        // 1. Standardize "No Nationality" / "بدون" / "محترف" to "Undefined" / "غير محدد"
        \App\Models\Player::where('nationality', 'No Nationality')
            ->orWhere('nationality_ar', 'بدون')
            ->orWhere('nationality', 'محترف')
            ->orWhere('nationality_ar', 'محترف')
            ->update([
                'nationality' => 'Undefined',
                'nationality_ar' => 'غير محدد'
            ]);

        // 2. Fix Jordanian spelling and Arabic-in-English-field entries
        \App\Models\Player::where('nationality', 'اردني')
            ->orWhere('nationality_ar', 'اردني')
            ->update([
                'nationality' => 'Jordanian',
                'nationality_ar' => 'أردني'
            ]);

        // 3. Fix Afghani standardization
        \App\Models\Player::where('nationality', 'أفغاني')
            ->orWhere('nationality_ar', 'أفغاني')
            ->update([
                'nationality' => 'Afghan',
                'nationality_ar' => 'أفغاني'
            ]);

        // 4. Fix Kuwaiti feminine form
        \App\Models\Player::where('nationality', 'كويتية')
            ->orWhere('nationality_ar', 'كويتية')
            ->update([
                'nationality' => 'Kuwaiti',
                'nationality_ar' => 'كويتي'
            ]);

        // 5. Fix other Arabic-in-English-field entries seen in tinker
        \App\Models\Player::where('nationality', 'عراقي')->update(['nationality' => 'Iraqi', 'nationality_ar' => 'عراقي']);
        \App\Models\Player::where('nationality', 'ليبي')->update(['nationality' => 'Libyan', 'nationality_ar' => 'ليبي']);
        \App\Models\Player::where('nationality', 'سعودي')->update(['nationality' => 'Saudi', 'nationality_ar' => 'سعودي']);
        \App\Models\Player::where('nationality', 'كويتي')->update(['nationality' => 'Kuwaiti', 'nationality_ar' => 'كويتي']);
        \App\Models\Player::where('nationality', 'مصري')->update(['nationality' => 'Egyptian', 'nationality_ar' => 'مصري']);
        \App\Models\Player::where('nationality', 'صربي')->update(['nationality' => 'Serbian', 'nationality_ar' => 'صربي']);
        \App\Models\Player::where('nationality', 'بحريني')->update(['nationality' => 'Bahraini', 'nationality_ar' => 'بحريني']);
        \App\Models\Player::where('nationality', 'جزائري')->update(['nationality' => 'Algerian', 'nationality_ar' => 'جزائري']);
        \App\Models\Player::where('nationality', 'تونسي')->update(['nationality' => 'Tunisian', 'nationality_ar' => 'تونسي']);
        \App\Models\Player::where('nationality', 'سوري')->update(['nationality' => 'Syrian', 'nationality_ar' => 'سوري']);
        \App\Models\Player::where('nationality', 'لبناني')->update(['nationality' => 'Lebanese', 'nationality_ar' => 'لبناني']);
        \App\Models\Player::where('nationality', 'صومالي')->update(['nationality' => 'Somali', 'nationality_ar' => 'صومالي']);
        \App\Models\Player::where('nationality', 'إيراني')->update(['nationality' => 'Iranian', 'nationality_ar' => 'إيراني']);
        \App\Models\Player::where('nationality', 'غير محدد')->update(['nationality' => 'Undefined', 'nationality_ar' => 'غير محدد']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
