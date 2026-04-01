<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedInteger('batch_size_override')->nullable()->after('scheduled_at');
            $table->unsignedInteger('delay_minutes_override')->nullable()->after('batch_size_override');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['batch_size_override', 'delay_minutes_override']);
        });
    }
};

