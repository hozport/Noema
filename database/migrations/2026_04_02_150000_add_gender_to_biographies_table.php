<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->string('gender', 1)->nullable()->after('race');
        });
    }

    public function down(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
