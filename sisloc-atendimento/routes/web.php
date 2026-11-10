<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CadastroController;
use App\Http\Controllers\Admin\FormularioPublicoController;
use App\Http\Controllers\Admin\MonitoramentoController;
use App\Http\Controllers\Admin\PausasController;
use App\Http\Controllers\Admin\PerfilController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AtendimentoFinalizadoController;
use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\FraseRapidaController;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\PausaColaboradorController;
use App\Http\Controllers\PortalChamadoController;
use Illuminate\Support\Facades\Route;

Route::get('/abrir-chamado', [PortalChamadoController::class, 'criar'])->name('portal.chamados.criar');
Route::post('/abrir-chamado', [PortalChamadoController::class, 'armazenar'])->middleware('throttle:5,1')->name('portal.chamados.armazenar');
Route::get('/chamado-registrado', [PortalChamadoController::class, 'confirmacao'])->name('portal.chamados.confirmacao');
Route::get('/atendimento/{token}', [PortalChamadoController::class, 'acompanhar'])->name('portal.chamados.show');
Route::get('/atendimento/{token}/mensagens', [PortalChamadoController::class, 'mensagens'])->middleware('throttle:60,1')->name('portal.chamados.mensagens');
Route::post('/atendimento/{token}/mensagens', [PortalChamadoController::class, 'responder'])->middleware('throttle:10,1')->name('portal.chamados.responder');
Route::get('/atendimento/{token}/anexos/{anexo}', [PortalChamadoController::class, 'baixarAnexo'])->name('portal.chamados.anexos');

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
    Route::get('/fila', [FilaController::class, 'index'])->middleware('can:fila.ver')->name('fila.index');
    Route::middleware('can:fila.ver')->group(function () {
        Route::get('/pausas', [PausaColaboradorController::class, 'index'])->name('pausas.index');
        Route::post('/pausas/iniciar', [PausaColaboradorController::class, 'iniciar'])->name('pausas.iniciar');
        Route::post('/pausas/retornar', [PausaColaboradorController::class, 'retornar'])->name('pausas.retornar');
        Route::post('/pausas/{pausa}/encerrar-admin', [PausaColaboradorController::class, 'encerrarComoAdministrador'])->middleware('can:cadastros.gerenciar')->name('pausas.encerrar-admin');
        Route::post('/pausas/forcar', [PausaColaboradorController::class, 'forcar'])->middleware('can:cadastros.gerenciar')->name('pausas.forcar');
        Route::post('/pausas/motivos', [PausaColaboradorController::class, 'criarMotivo'])->middleware('can:cadastros.gerenciar')->name('pausas.motivos.criar');
    });
    Route::get('/atendimentos-finalizados', [AtendimentoFinalizadoController::class, 'index'])->middleware('can:finalizados.ver')->name('finalizados.index');
    Route::get('/atendimentos-finalizados/exportar', [AtendimentoFinalizadoController::class, 'exportar'])->middleware('can:finalizados.exportar')->name('finalizados.exportar');

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
        Route::get('/chamados/{chamado}/mais-detalhamentos', [ChamadoController::class, 'detalhamentos'])->name('chamados.detalhamentos');
        Route::put('/chamados/{chamado}', [ChamadoController::class, 'atualizar'])->name('chamados.atualizar');
        Route::post('/chamados/{chamado}/mais-detalhamentos', [ChamadoController::class, 'atualizarMaisDetalhamentos'])->name('chamados.detalhamentos.atualizar');
        Route::get('/relatorios-atendimentos/{finalizacao}', [ChamadoController::class, 'relatorio'])->name('chamados.relatorios.show');
        Route::post('/chamados/{chamado}/atribuir', [ChamadoController::class, 'atribuir'])->name('chamados.atribuir');
        Route::post('/chamados/{chamado}/transferir', [ChamadoController::class, 'transferir'])->name('chamados.transferir');
        Route::post('/chamados/{chamado}/prioridade', [ChamadoController::class, 'prioridade'])->name('chamados.prioridade');
        Route::post('/chamados/{chamado}/status', [ChamadoController::class, 'status'])->name('chamados.status');
        Route::post('/chamados/{chamado}/responder', [ChamadoController::class, 'responder'])->name('chamados.responder');
        Route::put('/chamados/{chamado}/interacoes/{interacao}', [ChamadoController::class, 'editarMensagem'])->name('chamados.interacoes.editar');
        Route::post('/chamados/{chamado}/frases-rapidas', [FraseRapidaController::class, 'store'])->name('chamados.frases-rapidas.store');
        Route::put('/chamados/{chamado}/frases-rapidas/{frase}', [FraseRapidaController::class, 'update'])->name('chamados.frases-rapidas.update');
        Route::delete('/chamados/{chamado}/frases-rapidas/{frase}', [FraseRapidaController::class, 'destroy'])->name('chamados.frases-rapidas.destroy');
        Route::post('/chamados/{chamado}/finalizar', [ChamadoController::class, 'finalizar'])->name('chamados.finalizar');
        Route::post('/chamados/{chamado}/reabrir', [ChamadoController::class, 'reabrir'])->middleware('can:chamados.reabrir')->name('chamados.reabrir');
        Route::get('/anexos/{anexo}', [ChamadoController::class, 'baixarAnexo'])->name('anexos.baixar');
        Route::get('/chamados/{chamado}/mensagens', [ChamadoController::class, 'mensagens'])->name('chamados.mensagens');
        Route::post('/chamados/{chamado}/acesso-publico/revogar', [ChamadoController::class, 'revogarAcessoPublico'])->middleware('can:chamados.editar')->name('chamados.acesso.revogar');
    });

    // Painel administrativo
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])->middleware('can:usuarios.gerenciar');
        Route::resource('perfis', PerfilController::class)->parameters(['perfis' => 'perfil'])->except(['show'])->middleware('can:perfis.gerenciar');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('can:auditoria.ver')->name('auditoria');
        Route::put('/pausas', [PausasController::class, 'atualizar'])->middleware('can:cadastros.gerenciar')->name('pausas.atualizar');
        Route::get('/monitoramento', [MonitoramentoController::class, 'index'])->middleware('can:cadastros.gerenciar')->name('monitoramento.index');
        Route::put('/monitoramento', [MonitoramentoController::class, 'atualizar'])->middleware('can:cadastros.gerenciar')->name('monitoramento.atualizar');
        Route::get('/formulario-publico', [FormularioPublicoController::class, 'index'])->middleware('can:formulario.configurar')->name('formulario-publico.index');
        Route::post('/formulario-publico/campos', [FormularioPublicoController::class, 'criar'])->middleware('can:formulario.configurar')->name('formulario-publico.criar');
        Route::put('/formulario-publico/campos/{campo}', [FormularioPublicoController::class, 'atualizar'])->middleware('can:formulario.configurar')->name('formulario-publico.atualizar');

        Route::get('/cadastros/{tipo}', [CadastroController::class, 'index'])->name('cadastros.index');
        Route::post('/cadastros/{tipo}', [CadastroController::class, 'store'])->name('cadastros.store');
        Route::get('/cadastros/{tipo}/{id}', [CadastroController::class, 'edit'])->name('cadastros.edit');
        Route::put('/cadastros/{tipo}/{id}', [CadastroController::class, 'update'])->name('cadastros.update');
    });
});
