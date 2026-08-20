<?php

namespace App\Http\Requests;

use App\Models\Procedure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'problem' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['nullable', 'string', 'max:5000'],
            'ticket_notes' => ['nullable', 'string', 'max:5000'],
            'escalation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'problem' => 'problema / sintomas',
            'category_id' => 'categoria',
            'steps' => 'passos',
            'steps.*' => 'passo',
            'ticket_notes' => 'o que registar no ticket',
            'escalation' => 'quando escalar',
        ];
    }

    public function messages(): array
    {
        return [
            'steps.required' => 'Indique pelo menos um passo.',
            'steps.min' => 'Indique pelo menos um passo.',
            'category_id.required' => 'Escolha uma categoria.',
            'category_id.exists' => 'A categoria escolhida já não existe.',
        ];
    }

    /** Remove passos vazios antes de validar o mínimo. */
    protected function prepareForValidation(): void
    {
        $steps = array_values(array_filter(
            (array) $this->input('steps', []),
            fn ($s) => trim((string) $s) !== ''
        ));

        $this->merge(['steps' => $steps]);
    }
}
