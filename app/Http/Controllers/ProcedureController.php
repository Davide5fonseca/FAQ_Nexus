<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Procedure;
use App\Models\SafetyRule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Página de consulta e impressão. Cada pessoa vê apenas a sua área. */
class ProcedureController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $utilizador = $request->user();

        $procedures = Procedure::query()
            ->visivelPara($utilizador)
            ->with(['category', 'steps'])
            ->filter($filters)
            ->orderBy('reference_number')
            ->get();

        // Só mostra categorias que tenham procedimentos visíveis a esta pessoa.
        $categories = Category::whereHas('procedures',
            fn ($q) => $q->visivelPara($utilizador)
        )->orderBy('name')->get();

        return view('consulta.index', [
            'procedures' => $procedures,
            'categories' => $categories,
            'rules' => SafetyRule::orderBy('position')->get(),
            'filters' => $filters,
            'hasAny' => Procedure::visivelPara($utilizador)->exists(),
        ]);
    }

    public function printAll(Request $request): View
    {
        $filters = $this->filters($request);

        $procedures = Procedure::query()
            ->visivelPara($request->user())
            ->with(['category', 'steps'])
            ->filter($filters)
            ->orderBy('reference_number')
            ->get();

        return view('consulta.imprimir', [
            'procedures' => $procedures,
            'rules' => SafetyRule::orderBy('position')->get(),
        ]);
    }

    public function printOne(Request $request, Procedure $procedure): View
    {
        abort_unless($procedure->visivelPor($request->user()), 403);
        $procedure->load(['category', 'steps']);

        return view('consulta.imprimir', [
            'procedures' => collect([$procedure]),
            'rules' => collect(),
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'categoria' => (int) $request->query('categoria', 0) ?: null,
        ];
    }
}
