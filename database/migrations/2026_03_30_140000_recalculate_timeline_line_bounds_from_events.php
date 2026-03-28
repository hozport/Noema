<?php

use App\Models\Timeline\TimelineLine;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (TimelineLine::query()->orderBy('id')->cursor() as $line) {
            $line->recalculateBoundsFromEvents();
        }
    }

    public function down(): void
    {
        // необратимо: границы восстановить из бэкапа нельзя
    }
};
