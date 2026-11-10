<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Autenticavel;
use Illuminate\Notifications\Notifiable;

class Usuario extends Autenticavel
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $attributes = ['ativo' => true];

    protected $fillable = [
        'nome', 'email', 'password', 'perfil_id', 'departamento_id', 'ativo',
        'tema', 'foto_perfil', 'ultimo_acesso_em', 'trocar_senha',
    ];

    protected $hidden = ['password', 'remember_token'];

    private ?array $chavesPermissoes = null;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'ativo' => 'boolean',
            'trocar_senha' => 'boolean',
            'ultimo_acesso_em' => 'datetime',
        ];
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(Notificacao::class);
    }

    public function ehAdministrador(): bool
    {
        return $this->perfil?->slug === 'administrador';
    }

    public function temPermissao(string $chave): bool
    {
        if (! $this->ativo) {
            return false;
        }
        if ($this->ehAdministrador()) {
            return true;
        }
        $this->chavesPermissoes ??= $this->perfil?->permissoes()->pluck('chave')->all() ?? [];

        return in_array($chave, $this->chavesPermissoes, true);
    }

    public function urlFoto(): ?string
    {
        return $this->foto_perfil ? asset('storage/'.$this->foto_perfil) : null;
    }
}
