<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->unsignedSmallInteger('maps_default_width')->default(2000);
            $table->unsignedSmallInteger('maps_default_height')->default(2000);
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn(['maps_default_width', 'maps_default_height']);
        });
    }
};
