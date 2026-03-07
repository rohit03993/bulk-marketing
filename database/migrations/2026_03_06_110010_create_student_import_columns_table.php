<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_import_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_import_id')->constrained('student_imports')->cascadeOnDelete();
            $table->unsignedInteger('column_index');
            $table->string('column_name')->nullable();
            $table->string('target_field')->nullable(); // e.g. name, father_name, whatsapp_phone_primary, class_name, section_name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_import_columns');
    }
};

