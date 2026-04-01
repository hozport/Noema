<?php

namespace App\Markup;

enum EntityModule: int
{
    case MapStub = 1;
    case TimelineLine = 2;
    case BestiaryCreature = 3;
    case Biography = 4;
    case Faction = 5;

    public function label(): string
    {
        return match ($this) {
            self::MapStub => 'Объекты карты',
            self::TimelineLine => 'Линия таймлайна',
            self::BestiaryCreature => 'Бестиарий',
            self::Biography => 'Биография',
            self::Faction => 'Фракция',
        };
    }

}
