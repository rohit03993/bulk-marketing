<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('aisensy_templates', function (Blueprint $table) {
            $table->text('body')->nullable()->after('param_mappings');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->text('message_sent')->nullable()->after('template_params');
        });
    }

    public function down(): void
    {
        Schema::table('aisensy_templates', function (Blueprint $table) {
            $table->dropColumn('body');
        });
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('message_sent');
        });
    }
};
