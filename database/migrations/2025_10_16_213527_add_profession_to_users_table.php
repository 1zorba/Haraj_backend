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
            // إضافة عمود 'profession' من نوع string، يقبل القيمة الفارغة (nullable),
            // ووضعه بعد عمود 'role'
            $table->string('profession')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // عند التراجع، قم بحذف عمود 'profession'
            $table->dropColumn('profession');
        });
    }
};
