<?php

namespace App\Http\Controllers;

use App\Models\Chamado;

class PainelController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();
        $base = fn () => Chamado::visiveisPara($usuario);

        $indicadores = [
            'total' => $base()->count(),
            'abertos' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'aberto'))->count(),
            'em_atendimento' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'em_atendimento'))->count(),
            'aguardando_usuario' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'aguardando_usuario'))->count(),
            'atrasados' => $base()->atrasados()->count(),
            'sem_responsavel' => $base()->whereNull('agente_id')->whereHas('status', fn ($q) => $q->where('final', false))->count(),
            'resolvidos' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'resolvido'))->count(),
        ];

        $recentes = $base()->with(['status', 'prioridade', 'solicitante'])->latest()->limit(8)->get();

        return view('painel.index', compact('indicadores', 'recentes'));
    }
}
