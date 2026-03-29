<?php

namespace App\Http\Requests\Faction;

use Illuminate\Foundation\Http\FormRequest;

class CreateFactionTimelineLineRequest extends FormRequest
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
        return [
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
