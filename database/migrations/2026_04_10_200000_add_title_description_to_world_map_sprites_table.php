<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('world_map_sprites', function (Blueprint $table) {
            $table->string('title')->nullable()->after('pos_y');
            $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('world_map_sprites', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }
};
