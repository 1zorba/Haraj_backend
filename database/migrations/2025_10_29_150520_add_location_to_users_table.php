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
        Schema::table('users', function (Blueprint $table) {
            // نستخدم double للدقة العالية في الإحداثيات
            // nullable() تعني أن الحقل يمكن أن يكون فارغًا (اختياري)
            $table->double('latitude')->nullable()->after('profession'); // after() لوضع الحقل بعد حقل المهنة
            $table->double('longitude')->nullable()->after('latitude');
            $table->string('address')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'address']);
        });
    }
};
