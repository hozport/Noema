<?php

namespace App\Models\Worlds;

/**
 * Сеттинг мира (визуальный/тематический режим)
 *
 * Используется в настройках мира и на карте (шрифты подписей объектов и далее — глобальная стилистика).
 * В MVP три значения; по умолчанию — фэнтези.
 */
enum WorldSetting: string
{
    case Fantasy = 'fantasy';
    case ScienceFiction = 'science_fiction';
    case Modern = 'modern';

    /**
     * Основной шрифт подписи объекта на карте для Konva (одно семейство, кириллица и латиница).
     */
    public function mapObjectLabelFontFamily(): string
    {
        return match ($this) {
            self::Fantasy => 'Cormorant Garamond',
            self::ScienceFiction => 'Exo 2',
            self::Modern => 'Instrument Sans',
        };
    }

    /**
     * Подпись в UI (селект настроек мира).
     */
    public function label(): string
    {
        return match ($this) {
            self::Fantasy => 'Фэнтези',
            self::ScienceFiction => 'Научная фантастика',
            self::Modern => 'Современность',
        };
    }
}
