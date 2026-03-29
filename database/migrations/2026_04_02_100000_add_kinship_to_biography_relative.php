<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biography_relative', function (Blueprint $table) {
            $table->string('kinship', 32)->nullable()->after('relative_biography_id');
            $table->string('kinship_custom', 255)->nullable()->after('kinship');
        });
    }

    public function down(): void
    {
        Schema::table('biography_relative', function (Blueprint $table) {
            $table->dropColumn(['kinship', 'kinship_custom']);
        });
    }
};
