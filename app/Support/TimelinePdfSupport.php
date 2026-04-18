<?php

namespace App\Support;

use App\Models\Timeline\TimelineEvent;

/**
 * Вспомогательные функции для текстового экспорта таймлайна в PDF
 *
 * Форматирование даты события и извлечение описания из связанного источника
 * (биография / фракция), если оно есть.
 */
class TimelinePdfSupport
{
    /**
     * Строка даты события для документа (как на холсте: день.месяц.год).
     *
     * @param  TimelineEvent  $event  Событие таймлайна
     */
    public static function formatEventDate(TimelineEvent $event): string
    {
        return sprintf('%02d.%02d.%d', $event->day, $event->month, $event->epoch_year);
    }

    /**
     * Текст описания события из связанной записи биографии или фракции, если есть тело.
     *
     * @param  TimelineEvent  $event  Событие таймлайна
     */
    public static function eventDescription(TimelineEvent $event): ?string
    {
        if ($event->source_type === null || $event->source_id === null) {
            return null;
        }

        $event->loadMissing('source');
        $src = $event->source;
        if ($src === null) {
            return null;
        }

        $body = $src->body ?? null;
        if ($body === null || $body === '') {
            return null;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($body)));
        if ($plain === '') {
            return null;
        }

        return html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
