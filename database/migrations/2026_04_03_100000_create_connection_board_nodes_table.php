<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_board_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('meta')->nullable();
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->timestamps();

            $table->index(['world_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_board_nodes');
    }
};
