<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Procedure;
use App\Models\SafetyRule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Página de consulta (pública) e impressão. */
class ProcedureController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $procedures = Procedure::query()
            ->active()
            ->with(['category', 'steps'])
            ->filter($filters)
            ->orderBy('reference_number')
            ->get();

        return view('consulta.index', [
            'procedures' => $procedures,
            'categories' => Category::orderBy('name')->get(),
            'rules' => SafetyRule::orderBy('position')->get(),
            'filters' => $filters,
            'hasAny' => Procedure::active()->exists(),
        ]);
    }

    public function printAll(Request $request): View
    {
        $filters = $this->filters($request);

        $procedures = Procedure::query()
            ->active()
            ->with(['category', 'steps'])
            ->filter($filters)
            ->orderBy('reference_number')
            ->get();

        return view('consulta.imprimir', [
            'procedures' => $procedures,
            'rules' => SafetyRule::orderBy('position')->get(),
        ]);
    }

    public function printOne(Procedure $procedure): View
    {
        abort_if($procedure->is_archived && ! auth()->check(), 404);
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
            'nivel' => in_array((int) $request->query('nivel'), Procedure::LEVELS, true) ? (int) $request->query('nivel') : null,
        ];
    }
}
