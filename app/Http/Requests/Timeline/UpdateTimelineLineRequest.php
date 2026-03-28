<?php

namespace App\Http\Requests\Timeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTimelineLineRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('end_year') === '' || $this->input('end_year') === null) {
            $this->merge(['end_year' => null]);
        }
    }

    public function authorize(): bool
    {
        $world = $this->route('world');
        $line = $this->route('line');

        return $world && $line
            && (int) $this->user()->id === (int) $world->user_id
            && (int) $line->world_id === (int) $world->id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_year' => ['required', 'integer', 'min:0'],
            'end_year' => ['nullable', 'integer', 'min:0'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = $this->integer('start_year');
            $end = $this->input('end_year');
            if ($end !== null && $end !== '' && (int) $end < $start) {
                $validator->errors()->add('end_year', 'Год окончания не может быть меньше года начала.');
            }
        });
    }
}
