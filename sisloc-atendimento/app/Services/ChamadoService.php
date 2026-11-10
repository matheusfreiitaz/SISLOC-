<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\FinalizacaoChamado;
use App\Models\HistoricoChamado;
use App\Models\InteracaoChamado;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ChamadoService
{
    public function __construct(
        private AuditoriaService $auditoria,
        private NotificacaoService $notificacoes,
        private RelatorioAtendimentoService $relatorios,
        private MensagemChamadoSanitizer $sanitizador,
    ) {}

    public function criar(Usuario $autor, array $dados, array $arquivos = []): Chamado
    {
        return DB::transaction(function () use ($autor, $dados, $arquivos) {
            $prioridade = Prioridade::findOrFail($dados['prioridade_id']);

            $chamado = Chamado::create([
                'solicitante_id' => $dados['solicitante_id'] ?? $autor->id,
                'departamento_id' => $dados['departamento_id'],
                'categoria_id' => $dados['categoria_id'] ?? null,
                'subcategoria_id' => $dados['subcategoria_id'] ?? null,
                'assunto' => $dados['assunto'],
                'descricao' => $dados['descricao'],
                'prioridade_id' => $prioridade->id,
                'status_id' => StatusChamado::idPorSlug('aberto'),
                'prazo_em' => now()->addHours($prioridade->prazo_horas),
            ]);

            $chamado->update(['numero' => sprintf('%s%06d', now()->format('Y'), $chamado->id)]);

            $this->anexar($chamado, $autor, $arquivos);
            $this->historico($chamado, $autor, 'chamado', null, 'Chamado aberto');
            $this->auditoria->registrar('chamado.criado', 'chamado', $chamado->id, ['numero' => $chamado->numero], $autor->id);
            $this->notificacoes->notificarDepartamento(
                $chamado->departamento_id, 'chamado.novo', "Novo chamado {$chamado->numero}",
                $chamado->assunto, route('chamados.show', $chamado), $autor->id
            );

            return $chamado;
        });
    }

    public function editar(Chamado $chamado, Usuario $autor, array $dados): Chamado
    {
        return DB::transaction(function () use ($chamado, $autor, $dados) {
            foreach (['assunto', 'descricao', 'categoria_id', 'subcategoria_id'] as $campo) {
                if (array_key_exists($campo, $dados) && (string) $chamado->{$campo} !== (string) $dados[$campo]) {
                    $this->historico($chamado, $autor, $campo, $chamado->{$campo}, $dados[$campo]);
                    $chamado->{$campo} = $dados[$campo];
                }
            }
            $chamado->save();
            $this->auditoria->registrar('chamado.editado', 'chamado', $chamado->id, [], $autor->id);

            return $chamado;
        });
    }

    public function atualizarMaisDetalhamentos(Chamado $chamado, Usuario $autor, array $dados): void
    {
        DB::transaction(function () use ($chamado, $autor, $dados) {
            $chamado = Chamado::query()->lockForUpdate()->findOrFail($chamado->id);
            $camposAlterados = [];
            foreach (['demandas_recorrentes', 'tom_comunicacao_cliente'] as $campo) {
                $anterior = $chamado->{$campo};
                $novo = $dados[$campo] ?? null;
                if ($anterior !== $novo) {
                    $chamado->{$campo} = $novo;
                    $camposAlterados[] = $campo;
                }
            }
            $chamado->save();
            $this->auditoria->registrar('chamado.detalhamentos_atualizados', 'chamado', $chamado->id, [
                'campos' => $camposAlterados,
            ], $autor->id);
        });
    }

    public function atribuir(Chamado $chamado, ?Usuario $agente, Usuario $autor): void
    {
        DB::transaction(function () use ($chamado, $agente, $autor) {
            $chamado = Chamado::query()->lockForUpdate()->findOrFail($chamado->id);
            $this->bloquearFinalizado($chamado);
            if ($chamado->agente_id !== null) {
                throw ValidationException::withMessages([
                    'agente_id' => 'Este chamado já tem responsável. Para mudar o responsável, use Transferir atendimento.',
                ]);
            }
            if (! $agente
                || ! $agente->ativo
                || (int) $agente->departamento_id !== (int) $chamado->departamento_id
                || ! $agente->temPermissao('chamados.finalizar')) {
                throw ValidationException::withMessages([
                    'agente_id' => 'Selecione um agente ativo do departamento atual.',
                ]);
            }

            $anterior = $chamado->agente?->nome;
            $chamado->agente_id = $agente->id;
            if ($chamado->status->slug === 'aberto') {
                $this->historico($chamado, $autor, 'status', $chamado->status->nome, 'Em atendimento');
                $chamado->status_id = StatusChamado::idPorSlug('em_atendimento');
            }
            $chamado->save();

            $this->historico($chamado, $autor, 'agente', $anterior, $agente->nome);
            $bloqueiosAnteriores = array_map('intval', $chamado->finalizacao_bloqueada_usuarios ?? []);
            $bloqueiosAtuais = array_values(array_diff($bloqueiosAnteriores, [(int) $agente->id]));
            if ($bloqueiosAtuais !== $bloqueiosAnteriores) {
                $chamado->update(['finalizacao_bloqueada_usuarios' => $bloqueiosAtuais]);
                $this->historico($chamado, $autor, 'finalização desbloqueada', null, "{$agente->nome} recebeu o chamado de volta.");
            }
            $this->auditoria->registrar('chamado.atribuido', 'chamado', $chamado->id, ['agente_id' => $agente->id], $autor->id);
            $this->notificacoes->notificar($agente->id, 'chamado.atribuido', "Chamado {$chamado->numero} atribuído a você",
                $chamado->assunto, route('chamados.show', $chamado), $autor->id);
        });
    }

    public function transferirDepartamento(
        Chamado $chamado,
        int $departamentoId,
        Usuario $autor,
        Usuario $autorizador,
        int $prioridadeId,
        ?Usuario $novoAgente = null,
        string $motivo = 'Transferência solicitada',
        ?string $observacoes = null,
    ): void {
        $this->bloquearFinalizado($chamado);
        $departamento = Departamento::where('ativo', true)->findOrFail($departamentoId);
        $novaPrioridade = Prioridade::where('ativo', true)->findOrFail($prioridadeId);
        if (! $autorizador->ativo || ! in_array($autorizador->perfil?->slug, ['administrador', 'supervisor'], true)) {
            throw ValidationException::withMessages(['autorizador_id' => 'Selecione um supervisor ou administrador ativo como autorizador informado.']);
        }
        if ($novoAgente && ((int) $novoAgente->departamento_id !== $departamento->id
            || ! $novoAgente->temPermissao('chamados.finalizar'))) {
            throw ValidationException::withMessages(['agente_id' => 'Selecione um agente ativo do departamento de destino.']);
        }

        $chamadoId = $chamado->id;
        DB::transaction(function () use ($chamadoId, $departamentoId, $autor, $autorizador, $novoAgente, $motivo, $observacoes, $departamento, $novaPrioridade) {
            $chamado = Chamado::with(['departamento', 'agente', 'status', 'prioridade'])
                ->lockForUpdate()->findOrFail($chamadoId);
            $this->bloquearFinalizado($chamado);

            $departamentoAnterior = $chamado->departamento->nome;
            $departamentoAnteriorId = (int) $chamado->departamento_id;
            $agenteAnterior = $chamado->agente?->nome;
            $agenteAnteriorId = $chamado->agente_id;
            $statusAnterior = $chamado->status->nome;
            $prioridadeAnterior = $chamado->prioridade->nome;
            $departamentoNovo = $departamento;

            $chamado->departamento_id = $departamentoId;
            $chamado->agente_id = $novoAgente?->id;
            $chamado->prioridade_id = $novaPrioridade->id;
            $chamado->prazo_em = $chamado->created_at->copy()->addHours($novaPrioridade->prazo_horas);
            $bloqueiosAnteriores = array_map('intval', $chamado->finalizacao_bloqueada_usuarios ?? []);
            $bloqueiosAtuais = array_values(array_diff($bloqueiosAnteriores, [$novoAgente?->id]));
            $bloqueiosAtuais[] = (int) $autor->id;
            $bloqueiosAtuais = array_values(array_unique($bloqueiosAtuais));
            $chamado->finalizacao_bloqueada_usuarios = $bloqueiosAtuais;
            if ($departamentoId !== $departamentoAnteriorId) {
                $chamado->status_id = StatusChamado::idPorSlug($novoAgente ? 'em_atendimento' : 'aberto');
            } elseif ($novoAgente && $chamado->status->slug === 'aberto') {
                $chamado->status_id = StatusChamado::idPorSlug('em_atendimento');
            }
            $chamado->save();
            $statusNovo = StatusChamado::findOrFail($chamado->status_id)->nome;

            $this->historico($chamado, $autor, 'departamento', $departamentoAnterior, $departamentoNovo->nome);
            $this->historico($chamado, $autor, 'agente', $agenteAnterior, $novoAgente?->nome);
            if ($prioridadeAnterior !== $novaPrioridade->nome) {
                $this->historico($chamado, $autor, 'prioridade', $prioridadeAnterior, $novaPrioridade->nome);
            }
            $this->historico($chamado, $autor, 'motivo da transferência', null, $motivo);
            $perfilAutorizador = $autorizador->perfil->nome;
            $this->historico($chamado, $autor, 'autorizador informado', null, "{$autorizador->nome} · {$perfilAutorizador}");
            $this->historico($chamado, $autor, 'finalização bloqueada', null, "{$autor->nome} transferiu o chamado.");
            if (in_array((int) ($novoAgente?->id ?? 0), $bloqueiosAnteriores, true)) {
                $this->historico($chamado, $autor, 'finalização desbloqueada', null, "{$novoAgente->nome} recebeu o chamado de volta.");
            }
            if ($observacoes) {
                $this->historico($chamado, $autor, 'observações da transferência', null, $observacoes);
            }
            if ($statusNovo !== $statusAnterior) {
                $this->historico($chamado, $autor, 'status', $statusAnterior, $statusNovo);
            }
            $this->auditoria->registrar('chamado.transferido', 'chamado', $chamado->id, [
                'departamento_anterior' => $departamentoAnterior,
                'departamento_novo' => $departamentoNovo->nome,
                'agente_anterior' => $agenteAnterior,
                'agente_novo' => $novoAgente?->nome,
                'prioridade_anterior' => $prioridadeAnterior,
                'prioridade_nova' => $novaPrioridade->nome,
                'motivo' => $motivo,
                'autorizador_informado_id' => $autorizador->id,
                'autorizador_informado' => $autorizador->nome,
                'perfil_autorizador' => $perfilAutorizador,
                'finalizacao_bloqueada_usuarios' => $bloqueiosAtuais,
            ], $autor->id);
            $this->notificacoes->notificar($chamado->agente_id, 'chamado.transferido',
                "Chamado {$chamado->numero} transferido para você", $motivo, route('chamados.show', $chamado), $autor->id);
            $this->notificacoes->notificar($agenteAnteriorId, 'chamado.transferido',
                "Chamado {$chamado->numero} foi transferido", $motivo, route('chamados.show', $chamado), $autor->id);
            if ($departamentoId !== $departamentoAnteriorId && ! $novoAgente) {
                $this->notificacoes->notificarDepartamento($departamentoId, 'chamado.transferido',
                    "Chamado {$chamado->numero} transferido", $motivo, route('chamados.show', $chamado), $autor->id);
            }
            $this->notificacoes->notificar(
                $autorizador->id,
                'chamado.transferencia_informada',
                "Transferência do chamado {$chamado->numero} informada",
                "A transferência foi realizada por {$autor->nome}. Você foi informado como autorizador; esta notificação é apenas para ciência e não exige aprovação nem ação.",
                route('chamados.show', $chamado),
            );
        });
    }

    public function alterarPrioridade(Chamado $chamado, int $prioridadeId, Usuario $autor): void
    {
        $this->bloquearFinalizado($chamado);
        $nova = Prioridade::findOrFail($prioridadeId);

        DB::transaction(function () use ($chamado, $nova, $autor) {
            $this->historico($chamado, $autor, 'prioridade', $chamado->prioridade->nome, $nova->nome);
            $chamado->update([
                'prioridade_id' => $nova->id,
                'prazo_em' => $chamado->created_at->copy()->addHours($nova->prazo_horas),
            ]);
            $this->auditoria->registrar('chamado.prioridade_alterada', 'chamado', $chamado->id, ['prioridade' => $nova->nome], $autor->id);
        });
    }

    public function alterarStatus(Chamado $chamado, int $statusId, Usuario $autor, ?string $justificativa = null): void
    {
        $novo = StatusChamado::where('ativo', true)->findOrFail($statusId);
        $this->bloquearFinalizado($chamado);

        if ($novo->final) {
            throw ValidationException::withMessages(['status_id' => 'Para concluir um chamado utilize a opção Finalizar atendimento.']);
        }

        if ($chamado->status_id === $novo->id) {
            throw ValidationException::withMessages(['status_id' => 'Selecione um status diferente do atual.']);
        }

        if (in_array($novo->slug, ['aguardando_usuario', 'pendente', 'aguardando_terceiros'], true)
            && mb_strlen(trim((string) $justificativa)) < 5) {
            throw ValidationException::withMessages(['justificativa' => 'Informe pelo menos cinco caracteres sobre o que está sendo aguardado.']);
        }

        DB::transaction(function () use ($chamado, $novo, $autor, $justificativa) {
            $this->historico($chamado, $autor, 'status', $chamado->status->nome, $novo->nome);
            if ($justificativa !== null && trim($justificativa) !== '') {
                $this->historico($chamado, $autor, 'justificativa de status', null, trim($justificativa));
            }
            $chamado->update([
                'status_id' => $novo->id,
                'finalizado_em' => null,
            ]);
            $this->auditoria->registrar('chamado.status_alterado', 'chamado', $chamado->id, [
                'status' => $novo->nome,
                'justificativa' => $justificativa,
            ], $autor->id);
        });
    }

    public function responder(Chamado $chamado, Usuario $autor, string $mensagem, string $tipo, array $arquivos = []): InteracaoChamado
    {
        $this->bloquearFinalizado($chamado);
        $mensagem = $this->sanitizador->sanitizar($mensagem);
        $textoPlano = $this->sanitizador->textoSimples($mensagem);
        if (blank($textoPlano)) {
            throw ValidationException::withMessages(['mensagem' => 'Escreva uma mensagem antes de enviar.']);
        }

        $interacao = DB::transaction(function () use ($chamado, $autor, $mensagem, $textoPlano, $tipo, $arquivos) {
            $interacao = $chamado->interacoes()->create([
                'usuario_id' => $autor->id,
                'tipo' => $tipo,
                'mensagem' => $mensagem,
            ]);
            $this->anexar($chamado, $autor, $arquivos, $interacao->id);

            $ehSolicitante = $autor->id === $chamado->solicitante_id;

            if (! $ehSolicitante && $tipo === 'resposta') {
                $chamado->primeira_resposta_em ??= now();
            }
            if ($ehSolicitante && $chamado->status->slug === 'aguardando_usuario') {
                $this->historico($chamado, $autor, 'status', $chamado->status->nome, 'Em atendimento');
                $chamado->status_id = StatusChamado::idPorSlug('em_atendimento');
            }
            $chamado->touch();
            $chamado->save();

            if ($tipo === 'resposta') {
                $destino = $ehSolicitante ? $chamado->agente_id : $chamado->solicitante_id;
                $this->notificacoes->notificar($destino, 'chamado.resposta', "Nova resposta no chamado {$chamado->numero}",
                    mb_strimwidth($textoPlano, 0, 120, '…'), route('chamados.show', $chamado), $autor->id);
            }
            $this->auditoria->registrar('chamado.mensagem_registrada', 'chamado', $chamado->id, [
                'interacao_id' => $interacao->id,
                'tipo' => $tipo,
            ], $autor->id);

            return $interacao;
        });

        if ($tipo === 'resposta' && ! $chamado->solicitante_id && $chamado->solicitante_email) {
            Mail::raw(
                "Há uma nova resposta da equipe no chamado {$chamado->numero}.\n\nAcesse o link individual enviado na abertura do atendimento para consultar a conversa.",
                fn ($email) => $email->to($chamado->solicitante_email)->subject("Nova resposta no chamado {$chamado->numero}")
            );
        }

        return $interacao;
    }

    public function corrigirMensagem(Chamado $chamado, InteracaoChamado $interacao, Usuario $autor, string $mensagem): void
    {
        $mensagem = $this->sanitizador->sanitizar($mensagem);
        if (blank($this->sanitizador->textoSimples($mensagem))) {
            throw ValidationException::withMessages(['mensagem' => 'A mensagem corrigida não pode ficar vazia.']);
        }

        DB::transaction(function () use ($chamado, $interacao, $autor, $mensagem): void {
            $interacao = $chamado->interacoes()->whereKey($interacao->id)->lockForUpdate()->firstOrFail();
            abort_if($interacao->remetente_tipo === 'cliente' || $interacao->usuario_id !== $autor->id, 403);
            if ($interacao->mensagem === $mensagem) {
                return;
            }

            $interacao->edicoes()->create([
                'usuario_id' => $autor->id,
                'mensagem_anterior' => $interacao->mensagem,
            ]);
            $interacao->update(['mensagem' => $mensagem]);
            $this->auditoria->registrar('chamado.mensagem_corrigida', 'chamado', $chamado->id, [
                'interacao_id' => $interacao->id,
                'edicao_id' => $interacao->edicoes()->latest('id')->value('id'),
            ], $autor->id);
        });
    }

    public function finalizar(Chamado $chamado, Usuario $autor, int $motivoId, string $descricao, ?string $observacoes): FinalizacaoChamado
    {
        $this->bloquearFinalizado($chamado);

        return DB::transaction(function () use ($chamado, $autor, $motivoId, $descricao, $observacoes) {
            $chamado = Chamado::with('status')->lockForUpdate()->findOrFail($chamado->id);
            $this->bloquearFinalizado($chamado);
            $this->bloquearFinalizacaoPorTransferencia($chamado, $autor);

            $finalizacao = FinalizacaoChamado::create([
                'chamado_id' => $chamado->id,
                'motivo_id' => $motivoId,
                'usuario_id' => $autor->id,
                'descricao_solucao' => $descricao,
                'observacoes' => $observacoes,
            ]);

            $this->historico($chamado, $autor, 'status', $chamado->status->nome, 'Resolvido');
            $chamado->update([
                'status_id' => StatusChamado::idPorSlug('resolvido'),
                'agente_id' => $chamado->agente_id ?? $autor->id,
                'finalizado_em' => now(),
            ]);
            $finalizacao->load(['motivo', 'usuario']);
            $finalizacao->update(['relatorio' => $this->relatorios->gerar($chamado, $finalizacao)]);

            $this->auditoria->registrar('chamado.finalizado', 'chamado', $chamado->id, [
                'motivo_id' => $motivoId,
                'relatorio_gerado' => true,
                'metodo_relatorio' => 'estruturado_local',
            ], $autor->id);
            $this->notificacoes->notificar($chamado->solicitante_id, 'chamado.finalizado', "Chamado {$chamado->numero} finalizado",
                $descricao, route('chamados.show', $chamado), $autor->id);

            return $finalizacao;
        });
    }

    public function reabrir(Chamado $chamado, Usuario $autor): void
    {
        if (! $chamado->estaFinalizado()) {
            throw ValidationException::withMessages(['chamado' => 'Somente um atendimento finalizado pode ser reaberto.']);
        }

        DB::transaction(function () use ($chamado, $autor) {
            $anterior = $chamado->status->nome;
            $chamado->update([
                'status_id' => StatusChamado::idPorSlug('aberto'),
                'finalizado_em' => null,
            ]);
            $this->historico($chamado, $autor, 'reabertura', $anterior, 'Aberto');
            $this->auditoria->registrar('chamado.reaberto', 'chamado', $chamado->id, [], $autor->id);
            $this->notificacoes->notificar(
                $chamado->solicitante_id,
                'chamado.reaberto',
                "Chamado {$chamado->numero} reaberto",
                'O atendimento foi reaberto pela equipe.',
                $chamado->solicitante_id ? route('chamados.show', $chamado) : null,
                $autor->id,
            );
            $this->notificacoes->notificarDepartamento(
                $chamado->departamento_id,
                'chamado.reaberto',
                "Chamado {$chamado->numero} reaberto",
                $chamado->assunto,
                route('chamados.show', $chamado),
                $autor->id,
            );
        });
    }

    private function bloquearFinalizado(Chamado $chamado): void
    {
        if ($chamado->estaFinalizado()) {
            throw ValidationException::withMessages(['chamado' => 'Este chamado já foi finalizado e não pode mais ser alterado.']);
        }
    }

    private function bloquearFinalizacaoPorTransferencia(Chamado $chamado, Usuario $autor): void
    {
        if (in_array((int) $autor->id, array_map('intval', $chamado->finalizacao_bloqueada_usuarios ?? []), true)) {
            throw ValidationException::withMessages([
                'chamado' => 'Você transferiu este chamado. A finalização será liberada quando o setor responsável o devolver atribuído a você.',
            ]);
        }
    }

    private function historico(Chamado $chamado, ?Usuario $autor, string $campo, mixed $anterior, mixed $novo): void
    {
        HistoricoChamado::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $autor?->id,
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
        ]);
    }

    /** @param  array<int, UploadedFile>  $arquivos */
    private function anexar(Chamado $chamado, Usuario $autor, array $arquivos, ?int $interacaoId = null): void
    {
        foreach ($arquivos as $arquivo) {
            $chamado->anexos()->create([
                'interacao_id' => $interacaoId,
                'usuario_id' => $autor->id,
                'nome_original' => $arquivo->getClientOriginalName(),
                'caminho' => $arquivo->store("anexos/{$chamado->id}", 'local'),
                'mime' => $arquivo->getClientMimeType(),
                'tamanho' => $arquivo->getSize(),
            ]);
        }
    }
}
