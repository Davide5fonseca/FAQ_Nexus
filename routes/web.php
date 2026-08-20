<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProcedureController as AdminProcedureController;
use App\Http\Controllers\Admin\SafetyRuleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProcedureController;
use Illuminate\Support\Facades\Route;

// ---------- Consulta (requer sessão: toda a aplicação é interna) ----------
Route::middleware('auth')->group(function () {
    Route::get('/', [ProcedureController::class, 'index'])->name('consulta');
    Route::get('/imprimir', [ProcedureController::class, 'printAll'])->name('imprimir');
    Route::get('/procedimentos/{procedure}/imprimir', [ProcedureController::class, 'printOne'])->name('imprimir.um');
});

// ---------- Autenticação ----------
Route::middleware('guest')->group(function () {
    Route::get('/admin/entrar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/admin/entrar', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.submit');

    // Recuperação / definição de palavra-passe (também usada pelo email de convite)
    Route::get('/admin/recuperar', [PasswordResetController::class, 'pedirForm'])->name('password.request');
    Route::post('/admin/recuperar', [PasswordResetController::class, 'enviar'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/admin/repor/{token}', [PasswordResetController::class, 'reporForm'])->name('password.reset');
    Route::post('/admin/repor', [PasswordResetController::class, 'repor'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});
Route::post('/admin/sair', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Administração (requer sessão) ----------
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/procedimentos');

    // Procedimentos
    Route::get('procedimentos', [AdminProcedureController::class, 'index'])->name('procedimentos.index');
    Route::get('procedimentos/novo', [AdminProcedureController::class, 'create'])->name('procedimentos.create');
    Route::post('procedimentos', [AdminProcedureController::class, 'store'])->name('procedimentos.store');
    Route::get('procedimentos/{procedure}/editar', [AdminProcedureController::class, 'edit'])->name('procedimentos.edit');
    Route::put('procedimentos/{procedure}', [AdminProcedureController::class, 'update'])->name('procedimentos.update');
    Route::post('procedimentos/{procedure}/duplicar', [AdminProcedureController::class, 'duplicate'])->name('procedimentos.duplicate');
    Route::post('procedimentos/{procedure}/arquivar', [AdminProcedureController::class, 'archive'])->name('procedimentos.archive');
    Route::post('procedimentos/{procedure}/desarquivar', [AdminProcedureController::class, 'unarchive'])->name('procedimentos.unarchive');
    Route::delete('procedimentos/{procedure}', [AdminProcedureController::class, 'destroy'])->middleware('can:admin')->name('procedimentos.destroy');

    // ---- Só administradores a partir daqui ----
    Route::middleware('can:admin')->group(function () {

    // Utilizadores
    Route::get('utilizadores', [UserController::class, 'index'])->name('utilizadores.index');
    Route::get('utilizadores/novo', [UserController::class, 'create'])->name('utilizadores.create');
    Route::post('utilizadores', [UserController::class, 'store'])->name('utilizadores.store');
    Route::get('utilizadores/{user}/editar', [UserController::class, 'edit'])->name('utilizadores.edit');
    Route::put('utilizadores/{user}', [UserController::class, 'update'])->name('utilizadores.update');
    Route::delete('utilizadores/{user}', [UserController::class, 'destroy'])->name('utilizadores.destroy');
    Route::post('utilizadores/{user}/convite', [UserController::class, 'convite'])->name('utilizadores.convite');

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
