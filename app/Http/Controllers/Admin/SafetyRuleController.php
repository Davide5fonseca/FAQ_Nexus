<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SafetyRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SafetyRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.regras.index', [
            'rules' => SafetyRule::orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['content' => ['required', 'string', 'max:2000']],
            [],
            ['content' => 'texto da regra']
        );

        SafetyRule::create([
            'content' => trim($data['content']),
            'position' => ((int) SafetyRule::max('position')) + 1,
            'updated_by' => $request->user()->signature,
        ]);

        return redirect()->route('admin.regras.index')->with('status', 'Regra adicionada.');
    }

    public function update(Request $request, SafetyRule $rule): RedirectResponse
    {
        $data = $request->validate(
            ['content' => ['required', 'string', 'max:2000']],
            [],
            ['content' => 'texto da regra']
        );

        $rule->update(['content' => trim($data['content']), 'updated_by' => $request->user()->signature]);

        return redirect()->route('admin.regras.index')->with('status', 'Regra guardada.');
    }

    /** Move a regra uma posição para cima ou para baixo. */
    public function move(Request $request, SafetyRule $rule): RedirectResponse
    {
        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        DB::transaction(function () use ($rule, $direction) {
            $neighbour = $direction === 'up'
                ? SafetyRule::where('position', '<', $rule->position)->orderByDesc('position')->first()
                : SafetyRule::where('position', '>', $rule->position)->orderBy('position')->first();

            if ($neighbour) {
                [$a, $b] = [$rule->position, $neighbour->position];
                $rule->update(['position' => $b]);
                $neighbour->update(['position' => $a]);
            }
        });

        return redirect()->route('admin.regras.index');
    }

    public function destroy(SafetyRule $rule): RedirectResponse
    {
        $rule->delete();

        // Renumera para manter as posições seguidas (1, 2, 3...).
        SafetyRule::orderBy('position')->get()->each(fn ($r, $i) => $r->update(['position' => $i + 1]));

        return redirect()->route('admin.regras.index')->with('status', 'Regra eliminada.');
    }
}
