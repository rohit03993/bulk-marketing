<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->foreignId('student_call_id')->nullable()->after('student_id')
                  ->constrained('student_calls')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_call_id');
        });
    }
};
