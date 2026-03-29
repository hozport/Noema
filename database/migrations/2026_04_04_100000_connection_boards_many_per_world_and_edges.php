<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->timestamps();

            $table->index(['world_id', 'created_at']);
        });

        Schema::table('connection_board_nodes', function (Blueprint $table) {
            $table->foreignId('connection_board_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $worldIds = DB::table('connection_board_nodes')->select('world_id')->distinct()->pluck('world_id');
        foreach ($worldIds as $worldId) {
            $boardId = DB::table('connection_boards')->insertGetId([
                'world_id' => $worldId,
                'name' => 'Доска',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('connection_board_nodes')
                ->where('world_id', $worldId)
                ->update(['connection_board_id' => $boardId]);
        }

        Schema::table('connection_board_nodes', function (Blueprint $table) {
            $table->dropIndex(['world_id', 'kind']);
            $table->dropForeign(['world_id']);
            $table->dropColumn('world_id');
        });

        Schema::table('connection_board_nodes', function (Blueprint $table) {
            $table->unsignedBigInteger('connection_board_id')->nullable(false)->change();
            $table->index(['connection_board_id', 'kind']);
        });

        Schema::create('connection_board_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_node_id')->constrained('connection_board_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('connection_board_nodes')->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['connection_board_id', 'from_node_id', 'to_node_id'], 'connection_board_edges_board_endpoints_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_board_edges');

        Schema::table('connection_board_nodes', function (Blueprint $table) {
            $table->foreignId('world_id')->nullable()->constrained()->cascadeOnDelete();
        });

        $nodes = DB::table('connection_board_nodes')->select('id', 'connection_board_id')->get();
        foreach ($nodes as $node) {
            $wid = DB::table('connection_boards')->where('id', $node->connection_board_id)->value('world_id');
            DB::table('connection_board_nodes')->where('id', $node->id)->update(['world_id' => $wid]);
        }

        Schema::table('connection_board_nodes', function (Blueprint $table) {
            $table->dropForeign(['connection_board_id']);
            $table->dropColumn('connection_board_id');
        });

        Schema::table('connection_board_nodes', function (Blueprint $table) {
            $table->index(['world_id', 'kind']);
        });

        Schema::dropIfExists('connection_boards');
    }
};
