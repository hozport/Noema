<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biography_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biography_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('epoch_year')->nullable();
            $table->unsignedInteger('year_end')->nullable();
            $table->unsignedTinyInteger('month')->default(1);
            $table->unsignedTinyInteger('day')->default(1);
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['biography_id', 'epoch_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biography_events');
    }
};
