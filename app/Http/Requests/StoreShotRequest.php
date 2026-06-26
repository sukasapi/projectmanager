<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShotRequest extends FormRequest
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
        return [
            'scene_id' => ['required', 'integer', Rule::exists('kf_adegan', 'id')],
            'shot_code' => [
                'required', 'string', 'max:255',
                // Kode shot unik dalam satu scene.
                Rule::unique('kf_shot', 'shot_code')
                    ->where(fn ($q) => $q->where('scene_id', $this->input('scene_id'))),
            ],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'shot_code.unique' => 'Kode shot sudah dipakai pada scene ini.',
        ];
    }
}
