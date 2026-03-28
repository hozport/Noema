<?php

use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('start_year');
            $table->unsignedInteger('end_year')->nullable();
            $table->string('color', 32);
            $table->boolean('is_main')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['world_id', 'is_main']);
        });

        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_line_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('epoch_year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->boolean('breaks_line')->default(false);
            $table->nullableMorphs('source');
            $table->timestamps();

            $table->index(['timeline_line_id', 'epoch_year']);
        });

        Schema::create('biography_timeline_event', function (Blueprint $table) {
            $table->foreignId('biography_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timeline_event_id')->constrained('timeline_events')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['biography_id', 'timeline_event_id']);
        });

        foreach (DB::table('worlds')->pluck('id') as $worldId) {
            $worldId = (int) $worldId;
            if (DB::table('timeline_lines')->where('world_id', $worldId)->exists()) {
                continue;
            }
            TimelineBootstrapService::bootstrap(World::query()->findOrFail($worldId));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('biography_timeline_event');
        Schema::dropIfExists('timeline_events');
        Schema::dropIfExists('timeline_lines');
    }
};
