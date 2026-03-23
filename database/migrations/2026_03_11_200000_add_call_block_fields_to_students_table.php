<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'is_call_blocked')) {
                $table->boolean('is_call_blocked')->default(false)->after('next_followup_at');
            }
            if (! Schema::hasColumn('students', 'blocked_reason')) {
                $table->string('blocked_reason', 100)->nullable()->after('is_call_blocked');
            }
            if (! Schema::hasColumn('students', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('blocked_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'blocked_at')) {
                $table->dropColumn('blocked_at');
            }
            if (Schema::hasColumn('students', 'blocked_reason')) {
                $table->dropColumn('blocked_reason');
            }
            if (Schema::hasColumn('students', 'is_call_blocked')) {
                $table->dropColumn('is_call_blocked');
            }
        });
    }
};

