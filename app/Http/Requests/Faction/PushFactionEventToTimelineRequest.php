<?php

namespace App\Http\Requests\Faction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PushFactionEventToTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $world = $this->route('world');
        $faction = $this->route('faction');

        return $world && $faction
            && (int) $this->user()->id === (int) $world->user_id
            && (int) $faction->world_id === (int) $world->id;
    }

    public function rules(): array
    {
        $world = $this->route('world');
        $faction = $this->route('faction');

        return [
            'timeline_line_id' => [
                'required',
                'integer',
                Rule::exists('timeline_lines', 'id')->where('world_id', $world->id),
            ],
            'faction_event_id' => [
                'required',
                'integer',
                Rule::exists('faction_events', 'id')->where('faction_id', $faction->id),
            ],
        ];
    }
}
