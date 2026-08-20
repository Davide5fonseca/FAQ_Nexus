<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categorias.index', [
            'categories' => Category::withCount('procedures')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')]],
            ['name.unique' => 'Já existe uma categoria com esse nome.'],
            ['name' => 'nome']
        );

        Category::create(['name' => trim($data['name'])]);

        return redirect()->route('admin.categorias.index')->with('status', "Categoria «{$data['name']}» criada.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category->id)]],
            ['name.unique' => 'Já existe uma categoria com esse nome.'],
            ['name' => 'nome']
        );

        $category->update(['name' => trim($data['name'])]);

        return redirect()->route('admin.categorias.index')->with('status', 'Categoria guardada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $n = $category->procedures()->count();

        if ($n > 0) {
            $sufixo = $n === 1 ? 'procedimento associado' : 'procedimentos associados';

            return back()->withErrors([
                'category' => "Não é possível eliminar «{$category->name}»: ainda tem {$n} {$sufixo}. Mude-os primeiro para outra categoria.",
            ]);
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categorias.index')->with('status', "Categoria «{$name}» eliminada.");
    }
}
