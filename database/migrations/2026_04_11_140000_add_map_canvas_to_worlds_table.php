<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->json('map_drawing_lines')->nullable()->after('setting');
            $table->string('map_fill_path', 512)->nullable()->after('map_drawing_lines');
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn(['map_drawing_lines', 'map_fill_path']);
        });
    }
};
