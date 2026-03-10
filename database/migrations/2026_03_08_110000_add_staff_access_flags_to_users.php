<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_schools')->default(true)->after('is_admin');
            $table->boolean('can_access_students')->default(true)->after('can_access_schools');
            $table->boolean('can_access_campaigns')->default(true)->after('can_access_students');
            $table->boolean('can_access_templates')->default(true)->after('can_access_campaigns');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'can_access_schools',
                'can_access_students',
                'can_access_campaigns',
                'can_access_templates',
            ]);
        });
    }
};

