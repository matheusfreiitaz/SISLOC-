<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class PausaColaboradorService
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function motivos(): Collection
    {
        return DB::table('motivos_pausa')->orderBy('nome')->pluck('nome');
    }

    public function colaboradores(): Collection
    {
        return Usuario::query()
            ->where('ativo', true)
            ->whereHas('perfil.permissoes', fn ($query) => $query->where('permissoes.chave', 'fila.ver'))
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }

    public function colaboradorDisponivel(int $usuarioId): ?Usuario
    {
        return Usuario::query()
            ->whereKey($usuarioId)
            ->where('ativo', true)
            ->whereHas('perfil.permissoes', fn ($query) => $query->where('permissoes.chave', 'fila.ver'))
            ->first();
    }

    public function adicionarMotivo(string $nome, Usuario $autor): void
    {
        DB::transaction(function () use ($nome, $autor): void {
            $id = DB::table('motivos_pausa')->insertGetId([
                'nome' => $nome,
                'criado_por' => $autor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->auditoria->registrar('colaborador.motivo_pausa_criado', 'motivo_pausa', $id, [
                'nome' => $nome,
            ], $autor->id);
        });
    }

    public function configuracoes(): object
    {
        $configuracoes = DB::table('configuracoes_pausa')->where('id', 1)->first();
        if (! $configuracoes) {
            throw new LogicException('As configurações de pausa não foram inicializadas.');
        }
        if ((int) $configuracoes->duracao_minutos === 0) {
            $configuracoes->duracao_minutos = null;
        }

        return $configuracoes;
    }

    public function salvarConfiguracoes(int $limiteSimultaneas, ?int $duracaoMinutos, Usuario $autor): void
    {
        DB::table('configuracoes_pausa')->where('id', 1)->update([
            'limite_simultaneas' => $limiteSimultaneas,
            'duracao_minutos' => $duracaoMinutos ?? 0,
            'updated_at' => now(),
        ]);

        $this->auditoria->registrar('colaborador.pausas_configuradas', 'configuracao_pausa', 1, [
            'limite_simultaneas' => $limiteSimultaneas,
            'duracao_minutos' => $duracaoMinutos,
        ], $autor->id);
    }

    public function iniciar(Usuario $usuario, string $motivo): array
    {
        return $this->registrarInicio($usuario, $motivo, $usuario, false);
    }

    public function forcar(Usuario $colaborador, string $motivo, Usuario $autor): array
    {
        return $this->registrarInicio($colaborador, $motivo, $autor, true);
    }

    private function registrarInicio(Usuario $colaborador, string $motivo, Usuario $autor, bool $forcada): array
    {
        return DB::transaction(function () use ($colaborador, $motivo, $autor, $forcada): array {
            DB::table('configuracoes_pausa')->where('id', 1)->update(['updated_at' => now()]);
            $configuracoes = $this->configuracoes();
            $motivoPausaId = DB::table('motivos_pausa')->where('nome', $motivo)->value('id');
            if (! $motivoPausaId) {
                throw new LogicException('O motivo da pausa não está cadastrado.');
            }

            $ativaDoUsuario = DB::table('pausas_colaboradores')
                ->where('usuario_id', $colaborador->id)
                ->whereNull('retornada_em')
                ->lockForUpdate()
                ->first();

            if ($ativaDoUsuario) {
                return ['iniciada' => ! $forcada, 'ja_estava_em_pausa' => true];
            }

            if (! $forcada) {
                $emPausa = DB::table('pausas_colaboradores')
                    ->whereNull('retornada_em')
                    ->count();

                if ($emPausa >= $configuracoes->limite_simultaneas) {
                    return [
                        'iniciada' => false,
                        'proximas' => $this->proximasAoRetorno(3),
                    ];
                }
            }

            $inicio = now();
            $previstaAte = $configuracoes->duracao_minutos
                ? $inicio->copy()->addMinutes($configuracoes->duracao_minutos)
                : null;
            $id = DB::table('pausas_colaboradores')->insertGetId([
                'usuario_id' => $colaborador->id,
                'iniciada_por_usuario_id' => $autor->id,
                'motivo_pausa_id' => $motivoPausaId,
                'iniciada_em' => $inicio,
                'prevista_ate' => $previstaAte,
                'created_at' => $inicio,
                'updated_at' => $inicio,
            ]);

            $detalhes = [
                'motivo' => $motivo,
                'prevista_ate' => $previstaAte?->toDateTimeString(),
            ];
            if ($forcada) {
                $detalhes['colaborador_id'] = $colaborador->id;
            }
            $this->auditoria->registrar(
                $forcada ? 'colaborador.pausa_forcada' : 'colaborador.pausa_iniciada',
                'pausa_colaborador',
                $id,
                $detalhes,
                $autor->id,
            );

            return ['iniciada' => true, 'ja_estava_em_pausa' => false];
        }, 3);
    }

    public function retornar(Usuario $usuario): bool
    {
        return DB::transaction(function () use ($usuario): bool {
            DB::table('configuracoes_pausa')->where('id', 1)->update(['updated_at' => now()]);
            $pausa = DB::table('pausas_colaboradores')
                ->where('usuario_id', $usuario->id)
                ->whereNull('retornada_em')
                ->lockForUpdate()
                ->first();

            if (! $pausa) {
                return false;
            }

            DB::table('pausas_colaboradores')->where('id', $pausa->id)->update([
                'retornada_em' => now(),
                'updated_at' => now(),
            ]);
            $this->auditoria->registrar('colaborador.pausa_encerrada', 'pausa_colaborador', $pausa->id, [
                'iniciada_em' => $pausa->iniciada_em,
                'prevista_ate' => $pausa->prevista_ate,
                'retornada_em' => now()->toDateTimeString(),
            ], $usuario->id);

            return true;
        }, 3);
    }

    public function encerrarComoAdministrador(int $pausaId, Usuario $autor): ?string
    {
        return DB::transaction(function () use ($pausaId, $autor): ?string {
            $pausa = DB::table('pausas_colaboradores')
                ->join('usuarios', 'usuarios.id', '=', 'pausas_colaboradores.usuario_id')
                ->where('pausas_colaboradores.id', $pausaId)
                ->whereNull('pausas_colaboradores.retornada_em')
                ->lockForUpdate()
                ->first([
                    'pausas_colaboradores.id',
                    'pausas_colaboradores.usuario_id',
                    'pausas_colaboradores.iniciada_em',
                    'usuarios.nome',
                ]);

            if (! $pausa) {
                return null;
            }

            $retornadaEm = now();
            DB::table('pausas_colaboradores')->where('id', $pausa->id)->update([
                'retornada_em' => $retornadaEm,
                'updated_at' => $retornadaEm,
            ]);
            $this->auditoria->registrar('colaborador.pausa_encerrada_admin', 'pausa_colaborador', $pausa->id, [
                'colaborador_id' => $pausa->usuario_id,
                'iniciada_em' => $pausa->iniciada_em,
                'retornada_em' => $retornadaEm->toDateTimeString(),
            ], $autor->id);

            return $pausa->nome;
        }, 3);
    }

    public function pausaAtiva(int $usuarioId): ?object
    {
        return DB::table('pausas_colaboradores')
            ->leftJoin('motivos_pausa', 'motivos_pausa.id', '=', 'pausas_colaboradores.motivo_pausa_id')
            ->where('usuario_id', $usuarioId)
            ->whereNull('retornada_em')
            ->select('pausas_colaboradores.*')
            ->selectRaw('COALESCE(motivos_pausa.nome, pausas_colaboradores.motivo) as motivo')
            ->first();
    }

    public function proximasAoRetorno(?int $limite = 3): Collection
    {
        $consulta = DB::table('pausas_colaboradores')
            ->join('usuarios', 'usuarios.id', '=', 'pausas_colaboradores.usuario_id')
            ->leftJoin('departamentos', 'departamentos.id', '=', 'usuarios.departamento_id')
            ->leftJoin('motivos_pausa', 'motivos_pausa.id', '=', 'pausas_colaboradores.motivo_pausa_id')
            ->whereNull('pausas_colaboradores.retornada_em')
            ->orderByRaw('CASE WHEN pausas_colaboradores.prevista_ate IS NULL THEN 1 ELSE 0 END')
            ->orderBy('pausas_colaboradores.prevista_ate')
            ->orderBy('pausas_colaboradores.id');

        if ($limite !== null) {
            $consulta->limit($limite);
        }

        return $consulta->get([
                'pausas_colaboradores.id',
                DB::raw('COALESCE(motivos_pausa.nome, pausas_colaboradores.motivo) as motivo'),
                'pausas_colaboradores.iniciada_em',
                'pausas_colaboradores.prevista_ate',
                'usuarios.nome',
                'departamentos.nome as departamento',
            ]);
    }

    public function totalEmPausa(): int
    {
        return DB::table('pausas_colaboradores')->whereNull('retornada_em')->count();
    }

    public function historico(int $usuarioId, bool $visaoAdministrativa): LengthAwarePaginator
    {
        $consulta = DB::table('pausas_colaboradores')
            ->join('usuarios', 'usuarios.id', '=', 'pausas_colaboradores.usuario_id')
            ->leftJoin('departamentos', 'departamentos.id', '=', 'usuarios.departamento_id')
            ->leftJoin('motivos_pausa', 'motivos_pausa.id', '=', 'pausas_colaboradores.motivo_pausa_id')
            ->orderByDesc('pausas_colaboradores.iniciada_em')
            ->select([
                'pausas_colaboradores.id',
                'pausas_colaboradores.usuario_id',
                DB::raw('COALESCE(motivos_pausa.nome, pausas_colaboradores.motivo) as motivo'),
                'pausas_colaboradores.iniciada_em',
                'pausas_colaboradores.prevista_ate',
                'pausas_colaboradores.retornada_em',
                'usuarios.nome',
                'departamentos.nome as departamento',
            ]);

        if ($visaoAdministrativa) {
            $consulta->leftJoin('usuarios as iniciadores', 'iniciadores.id', '=', 'pausas_colaboradores.iniciada_por_usuario_id')
                ->addSelect('iniciadores.nome as iniciada_por');
        } else {
            $consulta->where('pausas_colaboradores.usuario_id', $usuarioId);
        }

        return $consulta->paginate(20)->withQueryString();
    }
}
