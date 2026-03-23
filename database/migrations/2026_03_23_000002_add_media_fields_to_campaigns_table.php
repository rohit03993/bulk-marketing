<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('media_type', 20)->nullable()->after('name'); // image, video, document
            $table->text('media_url')->nullable()->after('media_type');
            $table->string('media_filename')->nullable()->after('media_url');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_url', 'media_filename']);
        });
    }
};
