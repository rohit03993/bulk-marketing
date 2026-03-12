<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_calls', function (Blueprint $table) {
            $table->string('whatsapp_auto_status', 20)->nullable()->after('call_direction');
        });
    }

    public function down(): void
    {
        Schema::table('student_calls', function (Blueprint $table) {
            $table->dropColumn('whatsapp_auto_status');
        });
    }
};
