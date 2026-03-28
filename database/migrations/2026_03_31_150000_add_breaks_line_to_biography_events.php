<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biography_events', function (Blueprint $table) {
            $table->boolean('breaks_line')->default(false)->after('body');
        });

        $mainLineIds = DB::table('timeline_lines')->where('is_main', true)->pluck('id');
        if ($mainLineIds->isNotEmpty()) {
            DB::table('timeline_events')->whereIn('timeline_line_id', $mainLineIds)->update(['breaks_line' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('biography_events', function (Blueprint $table) {
            $table->dropColumn('breaks_line');
        });
    }
};
