<?php

namespace App\Http\Requests\Biography;

use Illuminate\Foundation\Http\FormRequest;

class CreateBiographyTimelineLineRequest extends FormRequest
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
        return [
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
