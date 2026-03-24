<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->after('content');
        });

        foreach (DB::table('cards')->orderBy('id')->get() as $row) {
            DB::table('cards')->where('id', $row->id)->update(['number' => $row->position]);
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable()->after('content');
        });

        foreach (DB::table('cards')->orderBy('id')->get() as $row) {
            DB::table('cards')->where('id', $row->id)->update(['position' => $row->number]);
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
