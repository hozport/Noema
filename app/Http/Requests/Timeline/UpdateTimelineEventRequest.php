<?php

namespace App\Http\Requests\Timeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTimelineEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $world = $this->route('world');
        $event = $this->route('timelineEvent');

        return $world && $event
            && (int) $this->user()->id === (int) $world->user_id
            && (int) $event->line->world_id === (int) $world->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'epoch_year' => ['required', 'integer', 'min:0'],
            'month' => ['required', 'integer', 'min:1', 'max:100'],
            'day' => ['required', 'integer', 'min:1', 'max:100'],
            'breaks_line' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $event = $this->route('timelineEvent');
            if ($event && $event->line->is_main && $this->boolean('breaks_line')) {
                $validator->errors()->add('breaks_line', 'На основной линии мира нельзя обрывать линию.');
            }
        });
    }
}
