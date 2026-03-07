<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('roll_number')->nullable();
            $table->string('admission_number')->nullable();
            $table->string('whatsapp_phone_primary', 20)->nullable();
            $table->string('whatsapp_phone_secondary', 20)->nullable();
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

