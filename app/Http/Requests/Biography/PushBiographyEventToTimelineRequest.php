<?php

namespace App\Http\Requests\Biography;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PushBiographyEventToTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $world = $this->route('world');
        $biography = $this->route('biography');

        return $world && $biography
            && (int) $this->user()->id === (int) $world->user_id
            && (int) $biography->world_id === (int) $world->id;
    }

    public function rules(): array
    {
        $world = $this->route('world');
        $biography = $this->route('biography');

        return [
            'timeline_line_id' => [
                'required',
                'integer',
                Rule::exists('timeline_lines', 'id')->where('world_id', $world->id),
            ],
            'biography_event_id' => [
                'required',
                'integer',
                Rule::exists('biography_events', 'id')->where('biography_id', $biography->id),
            ],
        ];
    }
}
