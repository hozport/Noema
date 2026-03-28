<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creatures', function (Blueprint $table) {
            $table->string('species_kind')->nullable()->after('scientific_name');
            $table->string('height_text', 120)->nullable()->after('image_path');
            $table->string('weight_text', 120)->nullable()->after('height_text');
            $table->string('lifespan_text', 120)->nullable()->after('weight_text');
            $table->text('short_description')->nullable()->after('lifespan_text');
            $table->text('full_description')->nullable()->after('short_description');
            $table->text('habitat_text')->nullable()->after('full_description');
            $table->json('food_custom')->nullable()->after('habitat_text');
        });

        Schema::create('creature_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creature_id')->constrained('creatures')->cascadeOnDelete();
            $table->foreignId('related_creature_id')->constrained('creatures')->cascadeOnDelete();
            $table->unique(['creature_id', 'related_creature_id']);
        });

        Schema::create('creature_food', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creature_id')->constrained('creatures')->cascadeOnDelete();
            $table->foreignId('food_creature_id')->constrained('creatures')->cascadeOnDelete();
            $table->unique(['creature_id', 'food_creature_id']);
        });

        Schema::create('creature_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creature_id')->constrained('creatures')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creature_gallery');
        Schema::dropIfExists('creature_food');
        Schema::dropIfExists('creature_related');

        Schema::table('creatures', function (Blueprint $table) {
            $table->dropColumn([
                'species_kind',
                'height_text',
                'weight_text',
                'lifespan_text',
                'short_description',
                'full_description',
                'habitat_text',
                'food_custom',
            ]);
        });
    }
};
