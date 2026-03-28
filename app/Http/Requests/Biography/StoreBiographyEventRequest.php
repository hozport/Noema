<?php

namespace App\Http\Requests\Biography;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBiographyEventRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('epoch_year') === '' || $this->input('epoch_year') === null) {
            $this->merge(['epoch_year' => null]);
        }
        if ($this->input('year_end') === '' || $this->input('year_end') === null) {
            $this->merge(['year_end' => null]);
        }
    }

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
        return [
            'title' => ['required', 'string', 'max:500'],
            'epoch_year' => ['nullable', 'integer', 'min:0'],
            'year_end' => ['nullable', 'integer', 'min:0'],
            'month' => ['required', 'integer', 'min:1', 'max:100'],
            'day' => ['required', 'integer', 'min:1', 'max:100'],
            'body' => ['nullable', 'string'],
            'breaks_line' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $y = $this->input('epoch_year');
            if ($y === null || $y === '') {
                return;
            }
            $ye = $this->input('year_end');
            if ($ye !== null && $ye !== '' && (int) $ye < (int) $y) {
                $validator->errors()->add('year_end', 'Конец периода не может быть раньше начала.');
            }
        });
    }
}
