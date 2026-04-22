<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Индексы для типичных выборок списка миров по пользователю и сортировки
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->index(['user_id', 'onoff', 'updated_at'], 'worlds_user_onoff_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('worlds', function (Blueprint $table) {
            $table->dropIndex('worlds_user_onoff_updated_idx');
        });
    }
};
