<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timeline_events', function (Blueprint $table) {
            $table->boolean('is_reference_marker')->default(false)->after('breaks_line');
        });

        $mainLineIds = DB::table('timeline_lines')->where('is_main', true)->pluck('id');
        foreach ($mainLineIds as $lineId) {
            $id = DB::table('timeline_events')
                ->where('timeline_line_id', $lineId)
                ->where('epoch_year', 0)
                ->where('month', 1)
                ->where('day', 1)
                ->whereNull('source_type')
                ->whereNull('source_id')
                ->orderBy('id')
                ->value('id');
            if ($id) {
                DB::table('timeline_events')->where('id', $id)->update(['is_reference_marker' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('timeline_events', function (Blueprint $table) {
            $table->dropColumn('is_reference_marker');
        });
    }
};
