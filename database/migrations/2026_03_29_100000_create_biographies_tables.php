<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biographies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('race')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->string('image_path')->nullable();
            $table->text('short_description')->nullable();
            $table->text('full_description')->nullable();
            $table->timestamps();
        });

        Schema::create('biography_relative', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biography_id')->constrained('biographies')->cascadeOnDelete();
            $table->foreignId('relative_biography_id')->constrained('biographies')->cascadeOnDelete();
            $table->unique(['biography_id', 'relative_biography_id']);
        });

        Schema::create('biography_friend', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biography_id')->constrained('biographies')->cascadeOnDelete();
            $table->foreignId('friend_biography_id')->constrained('biographies')->cascadeOnDelete();
            $table->unique(['biography_id', 'friend_biography_id']);
        });

        Schema::create('biography_enemy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biography_id')->constrained('biographies')->cascadeOnDelete();
            $table->foreignId('enemy_biography_id')->constrained('biographies')->cascadeOnDelete();
            $table->unique(['biography_id', 'enemy_biography_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biography_enemy');
        Schema::dropIfExists('biography_friend');
        Schema::dropIfExists('biography_relative');
        Schema::dropIfExists('biographies');
    }
};
