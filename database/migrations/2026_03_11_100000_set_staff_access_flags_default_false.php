<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Turn off all extra permissions for existing non-admin users (telecallers).
        DB::table('users')->where('is_admin', false)->update([
            'can_access_schools' => false,
            'can_access_campaigns' => false,
            'can_access_templates' => false,
        ]);

        // Change column defaults so future users also get false.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_schools')->default(false)->change();
            $table->boolean('can_access_campaigns')->default(false)->change();
            $table->boolean('can_access_templates')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_schools')->default(true)->change();
            $table->boolean('can_access_campaigns')->default(true)->change();
            $table->boolean('can_access_templates')->default(true)->change();
        });
    }
};
