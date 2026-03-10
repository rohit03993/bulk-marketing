<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_calls', function (Blueprint $table) {
            if (! Schema::hasColumn('student_calls', 'who_answered')) {
                $table->string('who_answered', 50)->nullable()->after('call_status');
            }
            if (! Schema::hasColumn('student_calls', 'tags')) {
                $table->json('tags')->nullable()->after('call_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_calls', function (Blueprint $table) {
            if (Schema::hasColumn('student_calls', 'who_answered')) {
                $table->dropColumn('who_answered');
            }
            if (Schema::hasColumn('student_calls', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
