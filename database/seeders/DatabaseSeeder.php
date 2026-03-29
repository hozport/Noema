<?php

namespace Database\Seeders;

use App\Models\Bestiary\Creature;
use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // WithoutModelEvents отключает User::creating — folder_token нужно задать явно.
        $user = User::updateOrCreate(
            ['email' => 'test@test.test'],
            [
                'name' => 'Test User',
                'password' => 'test',
                'folder_token' => 'testseeduserfolder01',
            ]
        );

        $world = World::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Пример мира'],
            [
                'annotation' => 'Описание мира для примера. Здесь может быть краткая аннотация проекта.',
                'onoff' => true,
            ]
        );

        if (! $world->timelineLines()->exists()) {
            TimelineBootstrapService::bootstrap($world);
        }

        Creature::firstOrCreate(
            ['world_id' => $world->id, 'name' => 'Аспид (тест)'],
            ['scientific_name' => 'Testus aspis']
        );
    }
}
