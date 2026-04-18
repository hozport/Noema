<?php

namespace App\Http\Requests\Timeline;

use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Faction\Faction;
use App\Models\Faction\FactionEvent;
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
            'biography_event_id' => [
                'nullable',
                'integer',
                Rule::exists('biography_events', 'id')->whereIn(
                    'biography_id',
                    Biography::query()->where('world_id', $world->id)->select('id')
                ),
            ],
            'faction_event_id' => [
                'nullable',
                'integer',
                Rule::exists('faction_events', 'id')->whereIn(
                    'faction_id',
                    Faction::query()->where('world_id', $world->id)->select('id')
                ),
            ],
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

            $nSources = (int) $this->filled('biography_event_id') + (int) $this->filled('faction_event_id');
            if ($nSources > 1) {
                $validator->errors()->add('biography_event_id', 'Укажите не больше одного источника (биография или фракция).');
            }

            if ($this->filled('biography_event_id')) {
                $be = BiographyEvent::query()->find((int) $this->input('biography_event_id'));
                if ($be && $be->isOnTimeline()) {
                    $validator->errors()->add('biography_event_id', 'Эта запись биографии уже вынесена на таймлайн.');
                }
            }
            if ($this->filled('faction_event_id')) {
                $fe = FactionEvent::query()->find((int) $this->input('faction_event_id'));
                if ($fe && $fe->isOnTimeline()) {
                    $validator->errors()->add('faction_event_id', 'Эта запись фракции уже вынесена на таймлайн.');
                }
            }
        });
    }
}
