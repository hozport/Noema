<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
