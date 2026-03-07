<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_imports', function (Blueprint $table) {
            $table->string('import_class_name')->nullable()->after('academic_session_id');
            $table->string('import_section_name')->nullable()->after('import_class_name');
        });
    }

    public function down(): void
    {
        Schema::table('student_imports', function (Blueprint $table) {
            $table->dropColumn(['import_class_name', 'import_section_name']);
        });
    }
};
