<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcedureRequest;
use App\Models\Category;
use App\Models\Procedure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcedureController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'categoria' => (int) $request->query('categoria', 0) ?: null,
        ];

        $procedures = Procedure::query()
            ->with('category')
            ->withCount('steps')
            ->filter($filters)
            // Mostra activos e arquivados: os arquivados ficam marcados com etiqueta
            // e continuam acessíveis para se poderem desarquivar.
            ->orderBy('reference_number')
            ->get();

        return view('admin.procedimentos.index', [
            'procedures' => $procedures,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'hasAny' => Procedure::exists(),
            'counts' => [
                'activos' => Procedure::whereNull('archived_at')->count(),
                'arquivados' => Procedure::whereNotNull('archived_at')->count(),
                'categorias' => Category::count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.procedimentos.form', [
            'procedure' => new Procedure(),
            'steps' => [''],
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ProcedureRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $procedure = DB::transaction(function () use ($data, $request) {
            $procedure = Procedure::create([
                'reference_number' => Procedure::nextReferenceNumber(),
                'title' => $data['title'],
                'problem' => $data['problem'] ?? null,
                'category_id' => $data['category_id'],
                'ticket_notes' => $data['ticket_notes'] ?? null,
                'escalation' => $data['escalation'] ?? null,
                'created_by' => $request->user()->signature,
                'updated_by' => $request->user()->signature,
            ]);
            $procedure->syncSteps($data['steps']);

            return $procedure;
        });

        return redirect()->route('admin.procedimentos.index')
            ->with('status', "Procedimento {$procedure->reference} criado.");
    }

    public function edit(Procedure $procedure): View
    {
        $procedure->load('steps');

        return view('admin.procedimentos.form', [
            'procedure' => $procedure,
            'steps' => $procedure->steps->pluck('content')->all() ?: [''],
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProcedureRequest $request, Procedure $procedure): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $procedure) {
            $procedure->update([
                'title' => $data['title'],
                'problem' => $data['problem'] ?? null,
                'category_id' => $data['category_id'],
                'ticket_notes' => $data['ticket_notes'] ?? null,
                'escalation' => $data['escalation'] ?? null,
                'updated_by' => $request->user()->signature,
            ]);
            $procedure->syncSteps($data['steps']);
        });

        return redirect()->route('admin.procedimentos.index')
            ->with('status', "Procedimento {$procedure->reference} guardado.");
    }

    public function archive(Request $request, Procedure $procedure): RedirectResponse
    {
        $procedure->update(['archived_at' => now(), 'updated_by' => $request->user()->signature]);

        return back()->with('status', "Procedimento {$procedure->reference} arquivado. Deixa de aparecer na consulta.");
    }

    public function unarchive(Request $request, Procedure $procedure): RedirectResponse
    {
        $procedure->update(['archived_at' => null, 'updated_by' => $request->user()->signature]);

        return back()->with('status', "Procedimento {$procedure->reference} voltou a estar activo.");
    }

    public function destroy(Procedure $procedure): RedirectResponse
    {
        $ref = $procedure->reference;
        $procedure->delete();

        return redirect()->route('admin.procedimentos.index')
            ->with('status', "Procedimento {$ref} apagado definitivamente.");
    }
}
