<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('shot_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('shot_at')->nullable()->after('shot_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('remember_token')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['shot_by']);
            $table->dropColumn(['shot_by', 'shot_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
