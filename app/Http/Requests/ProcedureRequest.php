<?php

namespace App\Http\Requests;

use App\Models\Anexo;
use App\Models\User;
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
            // Só o administrador escolhe a área; para os restantes é a sua.
            'area' => ['nullable', Rule::in(array_keys(User::AREAS))],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['nullable', 'string', 'max:5000'],
            'ticket_notes' => ['nullable', 'string', 'max:5000'],
            'escalation' => ['nullable', 'string', 'max:5000'],

            // Anexos: imagens e PDFs. O `mimes` valida pelo conteúdo do
            // ficheiro, não pelo nome — mudar a extensão não engana.
            'anexos' => ['nullable', 'array', 'max:'.Anexo::MAXIMO_POR_PROCEDIMENTO],
            'anexos.*' => [
                'file',
                'mimes:'.implode(',', Anexo::EXTENSOES),
                'max:'.Anexo::TAMANHO_MAXIMO_KB,
            ],
            'legendas' => ['nullable', 'array'],
            'legendas.*' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'problem' => 'problema / sintomas',
            'category_id' => 'categoria',
            'area' => 'área',
            'steps' => 'passos',
            'steps.*' => 'passo',
            'ticket_notes' => 'o que registar no ticket',
            'escalation' => 'quando escalar',
            'anexos' => 'anexos',
            'anexos.*' => 'anexo',
            'legendas.*' => 'legenda',
        ];
    }

    public function messages(): array
    {
        return [
            'steps.required' => 'Indique pelo menos um passo.',
            'steps.min' => 'Indique pelo menos um passo.',
            'category_id.required' => 'Escolha uma categoria.',
            'category_id.exists' => 'A categoria escolhida já não existe.',
            'area.in' => 'Escolha uma área válida.',
            'anexos.max' => 'Não é possível juntar mais de :max anexos de cada vez.',
            'anexos.*.mimes' => 'Só são aceites imagens (JPG, PNG, GIF, WEBP) e ficheiros PDF.',
            'anexos.*.max' => 'Cada anexo tem de ter menos de 10 MB.',
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
