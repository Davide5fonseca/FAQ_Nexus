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
            'nivel' => in_array((int) $request->query('nivel'), Procedure::LEVELS, true) ? (int) $request->query('nivel') : null,
            'estado' => in_array($request->query('estado'), ['activos', 'arquivados', 'todos'], true) ? $request->query('estado') : 'activos',
        ];

        $procedures = Procedure::query()
            ->with('category')
            ->withCount('steps')
            ->filter($filters)
            ->when($filters['estado'] === 'activos', fn ($q) => $q->whereNull('archived_at'))
            ->when($filters['estado'] === 'arquivados', fn ($q) => $q->whereNotNull('archived_at'))
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
            'procedure' => new Procedure(['level' => 1]),
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
                'level' => $data['level'],
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
                'level' => $data['level'],
                'ticket_notes' => $data['ticket_notes'] ?? null,
                'escalation' => $data['escalation'] ?? null,
                'updated_by' => $request->user()->signature,
            ]);
            $procedure->syncSteps($data['steps']);
        });

        return redirect()->route('admin.procedimentos.index')
            ->with('status', "Procedimento {$procedure->reference} guardado.");
    }

    public function duplicate(Request $request, Procedure $procedure): RedirectResponse
    {
        $procedure->load('steps');

        $copy = DB::transaction(function () use ($procedure, $request) {
            $copy = Procedure::create([
                'reference_number' => Procedure::nextReferenceNumber(),
                'title' => mb_substr('Cópia de '.$procedure->title, 0, 200),
                'problem' => $procedure->problem,
                'category_id' => $procedure->category_id,
                'level' => $procedure->level,
                'ticket_notes' => $procedure->ticket_notes,
                'escalation' => $procedure->escalation,
                'created_by' => $request->user()->signature,
                'updated_by' => $request->user()->signature,
            ]);
            $copy->syncSteps($procedure->steps->pluck('content')->all());

            return $copy;
        });

        return redirect()->route('admin.procedimentos.edit', $copy)
            ->with('status', "Procedimento duplicado como {$copy->reference}. Pode agora editá-lo.");
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
