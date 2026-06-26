<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shotId = $this->route('shot')?->id ?? $this->route('shot');
        $sceneId = $this->input('scene_id', $this->route('shot')?->scene_id);

        return [
            'scene_id' => ['sometimes', 'required', 'integer', Rule::exists('kf_adegan', 'id')],
            'shot_code' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('kf_shot', 'shot_code')
                    ->where(fn ($q) => $q->where('scene_id', $sceneId))
                    ->ignore($shotId),
            ],
            'duration_seconds' => ['sometimes', 'required', 'integer', 'min:0', 'max:86400'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
