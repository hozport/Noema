<?php

namespace App\Http\Requests\Timeline;

use App\Models\Timeline\TimelineLine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTimelineEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $world = $this->route('world');

        return $world && (int) $this->user()->id === (int) $world->user_id;
    }

    public function rules(): array
    {
        $world = $this->route('world');

        return [
            'timeline_line_id' => [
                'required',
                'integer',
                Rule::exists('timeline_lines', 'id')->where(fn ($q) => $q->where('world_id', $world->id)),
            ],
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

            $world = $this->route('world');
            $lineId = (int) $this->input('timeline_line_id');
            $line = TimelineLine::query()->where('world_id', $world->id)->find($lineId);
            if ($line && $line->is_main && $this->boolean('breaks_line')) {
                $validator->errors()->add('breaks_line', 'На основной линии мира нельзя обрывать линию.');
            }
        });
    }
}
