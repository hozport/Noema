<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Несколько карт на мир: таблица world_maps, спрайты привязаны к карте.
     *
     * Данные из worlds.map_drawing_lines / map_fill_path переносятся в одну карту «Карта» 3000×3000.
     */
    public function up(): void
    {
        Schema::create('world_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->json('map_drawing_lines')->nullable();
            $table->string('map_fill_path', 512)->nullable();
            $table->timestamps();
        });

        Schema::table('world_map_sprites', function (Blueprint $table) {
            $table->unsignedBigInteger('world_map_id')->nullable()->after('id');
        });

        foreach (DB::table('worlds')->orderBy('id')->get() as $row) {
            $lines = $row->map_drawing_lines ?? null;
            if (is_string($lines)) {
                $decoded = json_decode($lines, true);
                $lines = is_array($decoded) ? $decoded : null;
            }

            $mapId = DB::table('world_maps')->insertGetId([
                'world_id' => $row->id,
                'title' => 'Карта',
                'width' => 3000,
                'height' => 3000,
                'map_drawing_lines' => $lines !== null ? json_encode($lines) : null,
                'map_fill_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $oldFill = $row->map_fill_path ?? null;
            if (is_string($oldFill) && $oldFill !== '' && Storage::disk('public')->exists($oldFill)) {
                $newPath = 'worlds/'.$row->id.'/maps/'.$mapId.'/map_fill.png';
                Storage::disk('public')->makeDirectory(\dirname($newPath));
                Storage::disk('public')->move($oldFill, $newPath);
                DB::table('world_maps')->where('id', $mapId)->update(['map_fill_path' => $newPath]);
            }

            DB::table('world_map_sprites')->where('world_id', $row->id)->update(['world_map_id' => $mapId]);
        }

        Schema::table('world_map_sprites', function (Blueprint $table) {
            $table->dropForeign(['world_id']);
            $table->dropColumn('world_id');
        });

        Schema::table('world_map_sprites', function (Blueprint $table) {
            $table->foreign('world_map_id')->references('id')->on('world_maps')->cascadeOnDelete();
        });

        Schema::table('worlds', function (Blueprint $table) {
            $table->dropColumn(['map_drawing_lines', 'map_fill_path']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Миграция много-карт необратима: восстановите из бэкапа перед откатом.');
    }
};
