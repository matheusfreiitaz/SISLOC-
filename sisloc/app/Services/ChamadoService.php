<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\FinalizacaoChamado;
use App\Models\HistoricoChamado;
use App\Models\InteracaoChamado;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChamadoService
{
    public function __construct(
        private AuditoriaService $auditoria,
        private NotificacaoService $notificacoes,
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

    public function atribuir(Chamado $chamado, ?Usuario $agente, Usuario $autor): void
    {
        $this->bloquearFinalizado($chamado);

        DB::transaction(function () use ($chamado, $agente, $autor) {
            $anterior = $chamado->agente?->nome;
            $chamado->agente_id = $agente?->id;
            if ($agente && $chamado->status->slug === 'aberto') {
                $this->historico($chamado, $autor, 'status', $chamado->status->nome, 'Em atendimento');
                $chamado->status_id = StatusChamado::idPorSlug('em_atendimento');
            }
            $chamado->save();

            $this->historico($chamado, $autor, 'agente', $anterior, $agente?->nome);
            $this->auditoria->registrar('chamado.atribuido', 'chamado', $chamado->id, ['agente_id' => $agente?->id], $autor->id);
            $this->notificacoes->notificar($agente?->id, 'chamado.atribuido', "Chamado {$chamado->numero} atribuído a você",
                $chamado->assunto, route('chamados.show', $chamado), $autor->id);
        });
    }

    public function transferirDepartamento(Chamado $chamado, int $departamentoId, Usuario $autor): void
    {
        $this->bloquearFinalizado($chamado);

        DB::transaction(function () use ($chamado, $departamentoId, $autor) {
            $anterior = $chamado->departamento->nome;
            $chamado->departamento_id = $departamentoId;
            $chamado->agente_id = null;
            $chamado->status_id = StatusChamado::idPorSlug('aberto');
            $chamado->save();
            $chamado->load('departamento');

            $this->historico($chamado, $autor, 'departamento', $anterior, $chamado->departamento->nome);
            $this->auditoria->registrar('chamado.transferido', 'chamado', $chamado->id, ['departamento_id' => $departamentoId], $autor->id);
            $this->notificacoes->notificarDepartamento($departamentoId, 'chamado.transferido',
                "Chamado {$chamado->numero} transferido", $chamado->assunto, route('chamados.show', $chamado), $autor->id);
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

    public function alterarStatus(Chamado $chamado, int $statusId, Usuario $autor): void
    {
        $novo = StatusChamado::findOrFail($statusId);
        $this->bloquearFinalizado($chamado);

        if ($novo->final && $novo->slug === 'resolvido') {
            throw ValidationException::withMessages(['status_id' => 'Para resolver um chamado utilize a opção Finalizar atendimento.']);
        }

        DB::transaction(function () use ($chamado, $novo, $autor) {
            $this->historico($chamado, $autor, 'status', $chamado->status->nome, $novo->nome);
            $chamado->update([
                'status_id' => $novo->id,
                'finalizado_em' => $novo->final ? now() : null,
            ]);
            $this->auditoria->registrar('chamado.status_alterado', 'chamado', $chamado->id, ['status' => $novo->nome], $autor->id);
        });
    }

    public function responder(Chamado $chamado, Usuario $autor, string $mensagem, string $tipo, array $arquivos = []): InteracaoChamado
    {
        $this->bloquearFinalizado($chamado);

        return DB::transaction(function () use ($chamado, $autor, $mensagem, $tipo, $arquivos) {
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
                    mb_strimwidth($mensagem, 0, 120, '…'), route('chamados.show', $chamado), $autor->id);
            }

            return $interacao;
        });
    }

    public function finalizar(Chamado $chamado, Usuario $autor, int $motivoId, string $descricao, ?string $observacoes): FinalizacaoChamado
    {
        $this->bloquearFinalizado($chamado);

        return DB::transaction(function () use ($chamado, $autor, $motivoId, $descricao, $observacoes) {
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

            $this->auditoria->registrar('chamado.finalizado', 'chamado', $chamado->id, ['motivo_id' => $motivoId], $autor->id);
            $this->notificacoes->notificar($chamado->solicitante_id, 'chamado.finalizado', "Chamado {$chamado->numero} finalizado",
                $descricao, route('chamados.show', $chamado), $autor->id);

            return $finalizacao;
        });
    }

    private function bloquearFinalizado(Chamado $chamado): void
    {
        if ($chamado->estaFinalizado()) {
            throw ValidationException::withMessages(['chamado' => 'Este chamado já foi finalizado e não pode mais ser alterado.']);
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
