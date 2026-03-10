<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Assignment
            if (! Schema::hasColumn('students', 'assigned_to')) {
                $table->foreignId('assigned_to')
                    ->nullable()
                    ->after('lead_status')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('students', 'assigned_by')) {
                $table->foreignId('assigned_by')
                    ->nullable()
                    ->after('assigned_to')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('students', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            }

            // Call summary fields (denormalised for quick filters)
            if (! Schema::hasColumn('students', 'total_calls')) {
                $table->unsignedInteger('total_calls')->default(0)->after('assigned_at');
            }
            if (! Schema::hasColumn('students', 'last_call_at')) {
                $table->timestamp('last_call_at')->nullable()->after('total_calls');
            }
            if (! Schema::hasColumn('students', 'last_call_status')) {
                $table->string('last_call_status', 50)->nullable()->after('last_call_at');
            }
            if (! Schema::hasColumn('students', 'last_call_notes')) {
                $table->text('last_call_notes')->nullable()->after('last_call_status');
            }
            if (! Schema::hasColumn('students', 'next_followup_at')) {
                $table->timestamp('next_followup_at')->nullable()->after('last_call_notes');
            }
        });

        if (! Schema::hasTable('student_calls')) {
            Schema::create('student_calls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

                // Call details
                $table->enum('call_status', [
                    'connected',
                    'no_answer',
                    'busy',
                    'switched_off',
                    'not_reachable',
                    'wrong_number',
                    'callback',
                ]);
                $table->unsignedInteger('duration_minutes')->default(0);

                // Outcome
                $table->text('call_notes')->nullable();
                $table->string('status_changed_to', 50)->nullable();

                // Follow-up
                $table->timestamp('next_followup_at')->nullable();
                $table->string('followup_notes', 500)->nullable();

                // Timing
                $table->timestamp('called_at')->useCurrent();
                $table->timestamps();

                $table->index('student_id');
                $table->index('user_id');
                $table->index('called_at');
                $table->index('next_followup_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_calls')) {
            Schema::dropIfExists('student_calls');
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'next_followup_at')) {
                $table->dropColumn('next_followup_at');
            }
            if (Schema::hasColumn('students', 'last_call_notes')) {
                $table->dropColumn('last_call_notes');
            }
            if (Schema::hasColumn('students', 'last_call_status')) {
                $table->dropColumn('last_call_status');
            }
            if (Schema::hasColumn('students', 'last_call_at')) {
                $table->dropColumn('last_call_at');
            }
            if (Schema::hasColumn('students', 'total_calls')) {
                $table->dropColumn('total_calls');
            }
            if (Schema::hasColumn('students', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
            if (Schema::hasColumn('students', 'assigned_by')) {
                $table->dropForeign(['assigned_by']);
                $table->dropColumn('assigned_by');
            }
            if (Schema::hasColumn('students', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
        });
    }
};

