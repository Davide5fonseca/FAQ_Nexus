<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcedureRequest;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\User;
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

        $utilizador = $request->user();

        $procedures = Procedure::query()
            ->visivelPara($utilizador)
            ->with('category')
            ->withCount('steps')
            ->filter($filters)
            ->orderBy('reference_number')
            ->get();

        return view('admin.procedimentos.index', [
            'procedures' => $procedures,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'hasAny' => Procedure::visivelPara($utilizador)->exists(),
            'counts' => [
                'procedimentos' => Procedure::visivelPara($utilizador)->count(),
                'categorias' => Category::count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.procedimentos.form', [
            'procedure' => new Procedure(['area' => $request->user()->area]),
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
                'area' => $this->areaEscolhida($request, $data),
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

    public function edit(Request $request, Procedure $procedure): View
    {
        $this->autorizar($request, $procedure);
        $procedure->load('steps');

        return view('admin.procedimentos.form', [
            'procedure' => $procedure,
            'steps' => $procedure->steps->pluck('content')->all() ?: [''],
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProcedureRequest $request, Procedure $procedure): RedirectResponse
    {
        $this->autorizar($request, $procedure);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $procedure) {
            $procedure->update([
                'title' => $data['title'],
                'problem' => $data['problem'] ?? null,
                'category_id' => $data['category_id'],
                'area' => $this->areaEscolhida($request, $data, $procedure),
                'ticket_notes' => $data['ticket_notes'] ?? null,
                'escalation' => $data['escalation'] ?? null,
                'updated_by' => $request->user()->signature,
            ]);
            $procedure->syncSteps($data['steps']);
        });

        return redirect()->route('admin.procedimentos.index')
            ->with('status', "Procedimento {$procedure->reference} guardado.");
    }

    public function destroy(Request $request, Procedure $procedure): RedirectResponse
    {
        $this->autorizar($request, $procedure);
        $ref = $procedure->reference;
        $procedure->delete();

        return redirect()->route('admin.procedimentos.index')
            ->with('status', "Procedimento {$ref} eliminado.");
    }

    /** Ninguém mexe em procedimentos de outra área (excepto administradores). */
    private function autorizar(Request $request, Procedure $procedure): void
    {
        abort_unless($procedure->visivelPor($request->user()), 403);
    }

    /**
     * O administrador escolhe a área; quem não é administrador fica sempre
     * com a sua própria área (ou mantém a do procedimento que está a editar).
     */
    private function areaEscolhida(Request $request, array $data, ?Procedure $procedure = null): string
    {
        if ($request->user()->is_admin && filled($data['area'] ?? null)) {
            return $data['area'];
        }

        return $procedure?->area ?? $request->user()->area;
    }
}
