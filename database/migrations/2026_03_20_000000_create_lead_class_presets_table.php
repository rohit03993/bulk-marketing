<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_class_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('grade');
            $table->string('stream', 10); // NEET or JEE
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['grade', 'stream'], 'lead_class_presets_grade_stream_unique');
            $table->index(['is_active', 'display_order']);
        });

        // Seed defaults if table is empty (safe for fresh installs / existing migrations).
        $existing = DB::table('lead_class_presets')->count();
        if ($existing > 0) {
            return;
        }

        $defaults = [
            ['grade' => 9, 'stream' => 'NEET', 'display_order' => 1],
            ['grade' => 10, 'stream' => 'NEET', 'display_order' => 2],
            ['grade' => 11, 'stream' => 'NEET', 'display_order' => 3],
            ['grade' => 11, 'stream' => 'JEE', 'display_order' => 4],
            ['grade' => 12, 'stream' => 'NEET', 'display_order' => 5],
            ['grade' => 12, 'stream' => 'JEE', 'display_order' => 6],
            ['grade' => 13, 'stream' => 'NEET', 'display_order' => 7],
            ['grade' => 13, 'stream' => 'JEE', 'display_order' => 8],
        ];

        DB::table('lead_class_presets')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_class_presets');
    }
};

