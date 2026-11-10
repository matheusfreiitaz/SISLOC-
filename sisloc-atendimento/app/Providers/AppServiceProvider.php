<?php

namespace App\Providers;

use App\Models\Notificacao;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Habilidades no formato "modulo.acao" são resolvidas pelas permissões do perfil do usuário.
        Gate::before(function ($usuario, string $habilidade) {
            return str_contains($habilidade, '.') ? $usuario->temPermissao($habilidade) : null;
        });

        View::composer('layouts.app', function ($visao) {
            $usuario = auth()->user();
            $visao->with('totalNotificacoes', $usuario
                ? Notificacao::where('usuario_id', $usuario->id)->whereNull('lida_em')->count()
                : 0);
        });
    }
}
