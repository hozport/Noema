<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timeline_lines', function (Blueprint $table) {
            $table->foreignId('source_biography_id')
                ->nullable()
                ->after('world_id')
                ->constrained('biographies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timeline_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_biography_id');
        });
    }
};
