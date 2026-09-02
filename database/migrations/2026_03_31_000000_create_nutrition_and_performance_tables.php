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
        // 1. Player Physical Reports
        Schema::create('player_physical_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            
            $table->float('weight')->nullable(); // الوزن
            $table->float('height')->nullable(); // الطول
            $table->float('fat_percentage')->nullable(); // نسبة الدهون
            $table->float('muscle_mass')->nullable(); // الكتلة العضلية
            $table->float('body_mass_index')->nullable(); // مؤشر كتلة الجسم
            
            $table->text('physical_assessment')->nullable(); // تقييم الحالة البدنية
            $table->date('report_date')->default(now());
            
            $table->json('additional_metrics')->nullable(); // أي أرقام إضافية
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Player Progress Photos (صور تطور الجسم)
        Schema::create('player_progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->string('photo_url', 2048);
            $table->enum('view_type', ['FRONT', 'SIDE', 'BACK', 'OTHER'])->nullable();
            $table->date('captured_at')->default(now());
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 3. Nutrition Programs (البرامج الغذائية)
        Schema::create('nutrition_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            
            $table->string('title')->nullable(); // عنوان البرنامج
            $table->integer('daily_calories')->nullable(); // السعرات اليومية
            $table->integer('protein_grams')->nullable(); // البروتين
            $table->integer('carbs_grams')->nullable(); // الكارب
            $table->integer('fat_grams')->nullable(); // الدهون
            
            $table->text('meal_details')->nullable(); // تفاصيل الوجبات
            $table->text('supplements')->nullable(); // المكملات الغذائية
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Training Programs (البرامج التدريبية للأداء البدني)
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            
            $table->string('title')->nullable();
            $table->text('workout_plan')->nullable(); // الخطة التدريبية
            $table->text('recovery_plan')->nullable(); // خطة الاستشفاء
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('nutrition_programs');
        Schema::dropIfExists('player_progress_photos');
        Schema::dropIfExists('player_physical_reports');
    }
};
