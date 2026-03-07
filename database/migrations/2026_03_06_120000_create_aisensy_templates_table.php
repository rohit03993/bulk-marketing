<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aisensy_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('param_count')->default(0);
            $table->json('param_mappings')->nullable(); // ordered array of sources for each param
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aisensy_templates');
    }
};

