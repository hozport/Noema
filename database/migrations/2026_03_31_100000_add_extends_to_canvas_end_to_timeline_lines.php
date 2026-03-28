<?php

use App\Models\Biography\BiographyEvent;
use App\Models\Timeline\TimelineLine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timeline_lines', function (Blueprint $table) {
            $table->boolean('extends_to_canvas_end')->default(false)->after('end_year');
        });

        $lineIds = DB::table('timeline_events')
            ->where('source_type', BiographyEvent::class)
            ->distinct()
            ->pluck('timeline_line_id');

        if ($lineIds->isNotEmpty()) {
            DB::table('timeline_lines')
                ->whereIn('id', $lineIds)
                ->where('is_main', false)
                ->update(['extends_to_canvas_end' => true]);
        }

        foreach (TimelineLine::query()->orderBy('id')->cursor() as $line) {
            $line->recalculateBoundsFromEvents();
        }
    }

    public function down(): void
    {
        Schema::table('timeline_lines', function (Blueprint $table) {
            $table->dropColumn('extends_to_canvas_end');
        });
    }
};
