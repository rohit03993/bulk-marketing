<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Lead status on students
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'lead_status')) {
                $table->string('lead_status', 30)
                    ->default('lead')
                    ->after('status');
            }
        });

        // Tags table (generic so we can reuse later if needed)
        if (! Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('type', 50)->nullable(); // e.g. 'student_import'
                $table->timestamps();
            });
        }

        // Pivot between students and tags
        if (! Schema::hasTable('student_tag')) {
            Schema::create('student_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['student_id', 'tag_id']);
            });
        }

        // Optional tag name stored per import (so we know which tag to attach)
        Schema::table('student_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('student_imports', 'tag_name')) {
                $table->string('tag_name')->nullable()->after('import_section_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_imports', function (Blueprint $table) {
            if (Schema::hasColumn('student_imports', 'tag_name')) {
                $table->dropColumn('tag_name');
            }
        });

        if (Schema::hasTable('student_tag')) {
            Schema::dropIfExists('student_tag');
        }

        if (Schema::hasTable('tags')) {
            Schema::dropIfExists('tags');
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'lead_status')) {
                $table->dropColumn('lead_status');
            }
        });
    }
};

