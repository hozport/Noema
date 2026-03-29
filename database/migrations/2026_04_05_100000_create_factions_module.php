<?php

use App\Support\FactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32);
            $table->string('type_custom')->nullable();
            $table->text('short_description')->nullable();
            $table->text('full_description')->nullable();
            $table->text('geographic_stub')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('faction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faction_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('epoch_year')->nullable();
            $table->unsignedInteger('year_end')->nullable();
            $table->unsignedSmallInteger('month')->default(1);
            $table->unsignedSmallInteger('day')->default(1);
            $table->text('body')->nullable();
            $table->boolean('breaks_line')->default(false);
            $table->timestamps();
        });

        Schema::create('faction_biography', function (Blueprint $table) {
            $table->foreignId('faction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('biography_id')->constrained()->cascadeOnDelete();
            $table->primary(['faction_id', 'biography_id']);
        });

        Schema::create('faction_related', function (Blueprint $table) {
            $table->foreignId('faction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_faction_id')->constrained('factions')->cascadeOnDelete();
            $table->primary(['faction_id', 'related_faction_id']);
        });

        Schema::create('faction_enemy', function (Blueprint $table) {
            $table->foreignId('faction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enemy_faction_id')->constrained('factions')->cascadeOnDelete();
            $table->primary(['faction_id', 'enemy_faction_id']);
        });

        Schema::create('faction_timeline_event', function (Blueprint $table) {
            $table->foreignId('faction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timeline_event_id')->constrained()->cascadeOnDelete();
            $table->primary(['faction_id', 'timeline_event_id']);
        });

        Schema::table('timeline_lines', function (Blueprint $table) {
            $table->foreignId('source_faction_id')
                ->nullable()
                ->after('source_biography_id')
                ->constrained('factions')
                ->nullOnDelete();
        });

        Schema::table('biographies', function (Blueprint $table) {
            $table->foreignId('race_faction_id')
                ->nullable()
                ->after('race')
                ->constrained('factions')
                ->nullOnDelete();
        });

        $this->migrateBiographyRacesToFactions();

        Schema::table('biographies', function (Blueprint $table) {
            $table->dropColumn('race');
        });
    }

    public function down(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->string('race')->nullable()->after('name');
        });

        DB::table('biographies')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $name = null;
                if ($row->race_faction_id) {
                    $name = DB::table('factions')->where('id', $row->race_faction_id)->value('name');
                }
                DB::table('biographies')->where('id', $row->id)->update(['race' => $name]);
            }
        });

        Schema::table('biographies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('race_faction_id');
        });

        Schema::table('timeline_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_faction_id');
        });

        Schema::dropIfExists('faction_timeline_event');
        Schema::dropIfExists('faction_enemy');
        Schema::dropIfExists('faction_related');
        Schema::dropIfExists('faction_biography');
        Schema::dropIfExists('faction_events');
        Schema::dropIfExists('factions');
    }

    private function migrateBiographyRacesToFactions(): void
    {
        $rows = DB::table('biographies')
            ->whereNotNull('race')
            ->where('race', '!=', '')
            ->orderBy('id')
            ->get(['id', 'world_id', 'race']);

        foreach ($rows as $row) {
            $existing = DB::table('factions')
                ->where('world_id', $row->world_id)
                ->where('type', FactionType::RACE)
                ->where('name', $row->race)
                ->value('id');

            if ($existing) {
                DB::table('biographies')->where('id', $row->id)->update(['race_faction_id' => $existing]);

                continue;
            }

            $id = DB::table('factions')->insertGetId([
                'world_id' => $row->world_id,
                'name' => $row->race,
                'type' => FactionType::RACE,
                'type_custom' => null,
                'short_description' => null,
                'full_description' => null,
                'geographic_stub' => null,
                'image_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('biographies')->where('id', $row->id)->update(['race_faction_id' => $id]);
        }
    }
};
