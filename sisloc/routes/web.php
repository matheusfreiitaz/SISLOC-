<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CadastroController;
use App\Http\Controllers\Admin\PerfilController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\PainelController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AutenticacaoController::class, 'formularioLogin'])->name('login');
    Route::post('/entrar', [AutenticacaoController::class, 'entrar'])->middleware('throttle:10,1');
    Route::get('/esqueci-senha', [AutenticacaoController::class, 'formularioEsqueciSenha'])->name('password.request');
    Route::post('/esqueci-senha', [AutenticacaoController::class, 'enviarLinkRecuperacao'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [AutenticacaoController::class, 'formularioRedefinirSenha'])->name('password.reset');
    Route::post('/redefinir-senha', [AutenticacaoController::class, 'redefinirSenha'])->name('password.update');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/', fn () => redirect()->route('painel'));
    Route::post('/sair', [AutenticacaoController::class, 'sair'])->name('logout');
    Route::get('/painel', [PainelController::class, 'index'])->name('painel');

    // Conta do próprio usuário
    Route::get('/conta', [ContaController::class, 'index'])->name('conta');
    Route::post('/conta/tema', [ContaController::class, 'tema'])->name('conta.tema');
    Route::put('/conta/perfil', [ContaController::class, 'atualizarPerfil'])->name('conta.perfil');
    Route::put('/conta/senha', [ContaController::class, 'alterarSenha'])->name('conta.senha');

    // Notificações
    Route::get('/notificacoes', [NotificacaoController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes/ler-todas', [NotificacaoController::class, 'marcarTodasLidas'])->name('notificacoes.ler-todas');
    Route::post('/notificacoes/{notificacao}/ler', [NotificacaoController::class, 'marcarLida'])->name('notificacoes.ler');

    // Chamados (autorização por permissão + Policy)
    Route::middleware('can:chamados.ver')->group(function () {
        Route::get('/chamados', [ChamadoController::class, 'index'])->name('chamados.index');
        Route::get('/chamados/novo', [ChamadoController::class, 'create'])->middleware('can:chamados.criar')->name('chamados.create');
        Route::post('/chamados', [ChamadoController::class, 'store'])->middleware('can:chamados.criar')->name('chamados.store');
        Route::get('/chamados/{chamado}', [ChamadoController::class, 'show'])->name('chamados.show');
        Route::put('/chamados/{chamado}', [ChamadoController::class, 'atualizar'])->name('chamados.atualizar');
        Route::post('/chamados/{chamado}/atribuir', [ChamadoController::class, 'atribuir'])->name('chamados.atribuir');
        Route::post('/chamados/{chamado}/transferir', [ChamadoController::class, 'transferir'])->name('chamados.transferir');
        Route::post('/chamados/{chamado}/prioridade', [ChamadoController::class, 'prioridade'])->name('chamados.prioridade');
        Route::post('/chamados/{chamado}/status', [ChamadoController::class, 'status'])->name('chamados.status');
        Route::post('/chamados/{chamado}/responder', [ChamadoController::class, 'responder'])->name('chamados.responder');
        Route::post('/chamados/{chamado}/finalizar', [ChamadoController::class, 'finalizar'])->name('chamados.finalizar');
        Route::get('/anexos/{anexo}', [ChamadoController::class, 'baixarAnexo'])->name('anexos.baixar');
    });

    // Painel administrativo
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])->middleware('can:usuarios.gerenciar');
        Route::resource('perfis', PerfilController::class)->parameters(['perfis' => 'perfil'])->except(['show'])->middleware('can:perfis.gerenciar');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('can:auditoria.ver')->name('auditoria');

        Route::get('/cadastros/{tipo}', [CadastroController::class, 'index'])->name('cadastros.index');
        Route::post('/cadastros/{tipo}', [CadastroController::class, 'store'])->name('cadastros.store');
        Route::get('/cadastros/{tipo}/{id}', [CadastroController::class, 'edit'])->name('cadastros.edit');
        Route::put('/cadastros/{tipo}/{id}', [CadastroController::class, 'update'])->name('cadastros.update');
    });
});
