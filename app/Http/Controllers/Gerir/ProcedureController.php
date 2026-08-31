<?php

namespace App\Http\Controllers\Gerir;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcedureRequest;
use App\Models\Anexo;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        return view('gerir.procedimentos.index', [
            'procedures' => $procedures,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'hasAny' => Procedure::visivelPara($utilizador)->exists(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('gerir.procedimentos.form', [
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
            $this->guardarAnexos($request, $procedure);

            return $procedure;
        });

        return redirect()->route('gerir.procedimentos.index')
            ->with('status', "Procedimento {$procedure->reference} criado.");
    }

    public function edit(Request $request, Procedure $procedure): View
    {
        $this->autorizar($request, $procedure);
        $procedure->load('steps', 'anexos');

        return view('gerir.procedimentos.form', [
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
            $this->guardarAnexos($request, $procedure);
        });

        return redirect()->route('gerir.procedimentos.index')
            ->with('status', "Procedimento {$procedure->reference} guardado.");
    }

    public function destroy(Request $request, Procedure $procedure): RedirectResponse
    {
        $this->autorizar($request, $procedure);
        $ref = $procedure->reference;
        $procedure->delete();

        return redirect()->route('gerir.procedimentos.index')
            ->with('status', "Procedimento {$ref} eliminado.");
    }

    /** Retirar um anexo. O ficheiro sai do disco com o registo (ver o modelo). */
    public function destroyAnexo(Request $request, Procedure $procedure, Anexo $anexo): RedirectResponse
    {
        $this->autorizar($request, $procedure);
        abort_unless($anexo->procedure_id === $procedure->id, 404);

        $nome = $anexo->rotulo;
        $anexo->delete();

        return back()->with('status', "Anexo «{$nome}» removido.");
    }

    /**
     * Grava os ficheiros que vieram no formulário.
     *
     * O nome com que ficam no disco é gerado aqui e nunca vem do que a pessoa
     * carregou: um nome de ficheiro é texto escolhido por quem envia, e serviria
     * para escrever fora da pasta. O nome original guarda-se à parte, só para
     * ser mostrado e usado no download.
     */
    private function guardarAnexos(Request $request, Procedure $procedure): void
    {
        $ficheiros = array_filter((array) $request->file('anexos'));

        if ($ficheiros === []) {
            return;
        }

        $jaTem = $procedure->anexos()->count();
        $cabem = max(0, Anexo::MAXIMO_POR_PROCEDIMENTO - $jaTem);
        $ficheiros = array_slice($ficheiros, 0, $cabem);

        $legendas = (array) $request->input('legendas', []);
        $ordem = (int) $procedure->anexos()->max('ordem');

        foreach ($ficheiros as $i => $ficheiro) {
            /** @var UploadedFile $ficheiro */
            $extensao = strtolower($ficheiro->extension() ?: $ficheiro->getClientOriginalExtension());
            $nomeNoDisco = Str::uuid()->toString().'.'.$extensao;

            $ficheiro->storeAs((string) $procedure->id, $nomeNoDisco, Anexo::DISCO);

            $procedure->anexos()->create([
                'ficheiro' => $nomeNoDisco,
                'nome_original' => mb_substr($ficheiro->getClientOriginalName(), 0, 255),
                'tipo' => $ficheiro->getMimeType() ?: 'application/octet-stream',
                'tamanho' => $ficheiro->getSize(),
                'legenda' => trim((string) ($legendas[$i] ?? '')) ?: null,
                'ordem' => ++$ordem,
                'criado_por' => $request->user()->signature,
            ]);
        }
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
