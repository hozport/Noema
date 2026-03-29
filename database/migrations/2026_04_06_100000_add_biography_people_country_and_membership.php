<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->foreignId('people_faction_id')
                ->nullable()
                ->after('race_faction_id')
                ->constrained('factions')
                ->nullOnDelete();
            $table->foreignId('country_faction_id')
                ->nullable()
                ->after('people_faction_id')
                ->constrained('factions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_faction_id');
            $table->dropConstrainedForeignId('people_faction_id');
        });
    }
};
