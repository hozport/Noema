<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Удаляет второстепенные линии без привязки к биографии.
     * Сохраняются: основная линия мира (is_main) и линии из биографии (source_biography_id).
     */
    public function up(): void
    {
        DB::table('timeline_lines')
            ->where('is_main', false)
            ->whereNull('source_biography_id')
            ->delete();
    }

    public function down(): void
    {
        // Необратимо: удалённые линии и события на них не восстанавливаются.
    }
};
