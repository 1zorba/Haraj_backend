<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // في ملف database/migrations/xxxx_xx_xx_create_job_requests_table.php

public function up(): void
{
    Schema::create('job_requests', function (Blueprint $table) {
        $table->id();

        // من أرسل الطلب (صاحب العمل)
        $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');

        // لمن تم إرسال الطلب (العامل)
        $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');

        // حالة الطلب (pending, accepted, rejected, completed)
        $table->string('status')->default('pending');

        // تفاصيل إضافية عن الطلب (اختياري)
        $table->text('details')->nullable();
        
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_requests');
    }
};
