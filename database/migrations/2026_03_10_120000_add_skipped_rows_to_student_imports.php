<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('student_imports', 'skipped_count')) {
                $table->unsignedInteger('skipped_count')->default(0)->after('processed_rows');
            }
            if (! Schema::hasColumn('student_imports', 'skipped_rows')) {
                $table->json('skipped_rows')->nullable()->after('skipped_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_imports', function (Blueprint $table) {
            if (Schema::hasColumn('student_imports', 'skipped_count')) {
                $table->dropColumn('skipped_count');
            }
            if (Schema::hasColumn('student_imports', 'skipped_rows')) {
                $table->dropColumn('skipped_rows');
            }
        });
    }
};
