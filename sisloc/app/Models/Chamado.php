<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chamado extends Model
{
    protected $table = 'chamados';

    protected $fillable = [
        'numero', 'solicitante_id', 'departamento_id', 'categoria_id', 'subcategoria_id', 'assunto',
        'descricao', 'prioridade_id', 'status_id', 'agente_id', 'prazo_em', 'primeira_resposta_em', 'finalizado_em',
    ];

    protected $casts = [
        'prazo_em' => 'datetime',
        'primeira_resposta_em' => 'datetime',
        'finalizado_em' => 'datetime',
    ];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'agente_id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'subcategoria_id');
    }

    public function prioridade(): BelongsTo
    {
        return $this->belongsTo(Prioridade::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusChamado::class, 'status_id');
    }

    public function interacoes(): HasMany
    {
        return $this->hasMany(InteracaoChamado::class)->orderBy('created_at');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoChamado::class);
    }

    public function historico(): HasMany
    {
        return $this->hasMany(HistoricoChamado::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function finalizacao(): HasOne
    {
        return $this->hasOne(FinalizacaoChamado::class)->latestOfMany();
    }

    public function estaAtrasado(): bool
    {
        return $this->prazo_em !== null
            && $this->prazo_em->isPast()
            && ! $this->status->final;
    }

    public function estaFinalizado(): bool
    {
        return (bool) $this->status->final;
    }

    /** Restringe os chamados ao que o usuário tem direito de ver. */
    public function scopeVisiveisPara(Builder $consulta, Usuario $usuario): Builder
    {
        if (! $usuario->temPermissao('chamados.ver')) {
            return $consulta->whereRaw('1 = 0');
        }
        if ($usuario->temPermissao('chamados.ver_todos')) {
            return $consulta;
        }

        return $consulta->where(function (Builder $q) use ($usuario) {
            $q->where('solicitante_id', $usuario->id)->orWhere('agente_id', $usuario->id);
            if ($usuario->departamento_id) {
                $q->orWhere('departamento_id', $usuario->departamento_id);
            }
        });
    }

    public function scopeAtrasados(Builder $consulta): Builder
    {
        return $consulta->where('prazo_em', '<', now())
            ->whereHas('status', fn ($q) => $q->where('final', false));
    }
}
