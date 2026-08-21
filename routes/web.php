<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProcedureController as AdminProcedureController;
use App\Http\Controllers\Admin\SafetyRuleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProcedureController;
use Illuminate\Support\Facades\Route;

// A entrada é feita no portal, que é quem trata do login e da verificação em
// duas etapas. Aqui só se recebe quem já vem autenticado e com acesso a esta
// aplicação (ver o middleware ExigeAcessoAplicacao, no grupo de baixo).
Route::get('/entrar', fn () => redirect()->away(config('app.portal_url')))->name('login');

// ---------- Consulta ----------
Route::middleware(['auth', 'acesso'])->group(function () {
    Route::get('/', [ProcedureController::class, 'index'])->name('consulta');
    Route::get('/imprimir', [ProcedureController::class, 'printAll'])->name('imprimir');
    Route::get('/procedimentos/{procedure}/imprimir', [ProcedureController::class, 'printOne'])->name('imprimir.um');
});

// ---------- Administração (requer permissão de edição) ----------
Route::middleware(['auth', 'acesso', 'can:editar'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/procedimentos');

    // Procedimentos
    Route::get('procedimentos', [AdminProcedureController::class, 'index'])->name('procedimentos.index');
    Route::get('procedimentos/novo', [AdminProcedureController::class, 'create'])->name('procedimentos.create');
    Route::post('procedimentos', [AdminProcedureController::class, 'store'])->name('procedimentos.store');
    Route::get('procedimentos/{procedure}/editar', [AdminProcedureController::class, 'edit'])->name('procedimentos.edit');
    Route::put('procedimentos/{procedure}', [AdminProcedureController::class, 'update'])->name('procedimentos.update');
    Route::delete('procedimentos/{procedure}', [AdminProcedureController::class, 'destroy'])->middleware('can:admin')->name('procedimentos.destroy');

    // ---- Só administradores a partir daqui ----
    Route::middleware('can:admin')->group(function () {

    // Perfis de quem tem acesso a esta aplicação
    Route::get('utilizadores', [UserController::class, 'index'])->name('utilizadores.index');
    Route::get('utilizadores/{utilizador}/editar', [UserController::class, 'edit'])->name('utilizadores.edit');
    Route::put('utilizadores/{utilizador}', [UserController::class, 'update'])->name('utilizadores.update');

    // Categorias
    Route::get('categorias', [CategoryController::class, 'index'])->name('categorias.index');
    Route::post('categorias', [CategoryController::class, 'store'])->name('categorias.store');
    Route::put('categorias/{category}', [CategoryController::class, 'update'])->name('categorias.update');
    Route::delete('categorias/{category}', [CategoryController::class, 'destroy'])->name('categorias.destroy');

    // Regras de segurança
    Route::get('regras-seguranca', [SafetyRuleController::class, 'index'])->name('regras.index');
    Route::post('regras-seguranca', [SafetyRuleController::class, 'store'])->name('regras.store');
    Route::put('regras-seguranca/{rule}', [SafetyRuleController::class, 'update'])->name('regras.update');
    Route::post('regras-seguranca/{rule}/mover', [SafetyRuleController::class, 'move'])->name('regras.move');
    Route::delete('regras-seguranca/{rule}', [SafetyRuleController::class, 'destroy'])->name('regras.destroy');

    }); // fim: só administradores
});
