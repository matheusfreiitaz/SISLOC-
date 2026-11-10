<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Notificacao;
use App\Models\Usuario;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    /** Somente leitura: não existem rotas de edição ou exclusão da auditoria. */
    public function index(Request $r)
    {
        $registros = Auditoria::with('usuario')
            ->when($r->filled('acao'), fn ($q) => $q->where('acao', 'like', $r->string('acao').'%'))
            ->when($r->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $r->integer('usuario_id')))
            ->orderByDesc('id')->paginate(30)->withQueryString();
        $colaboradores = Usuario::with('perfil')
            ->whereHas('perfil.permissoes', fn ($q) => $q->where('chave', 'fila.ver'))
            ->orderBy('nome')->get();
        $resumoNotificacoes = Notificacao::query()
            ->selectRaw('usuario_id, COUNT(*) as total, SUM(CASE WHEN lida_em IS NULL THEN 1 ELSE 0 END) as pendentes, SUM(CASE WHEN tipo LIKE ? THEN 1 ELSE 0 END) as alertas_atencao', ['monitoramento.%'])
            ->groupBy('usuario_id')
            ->get()
            ->keyBy('usuario_id');

        return view('admin.auditoria', compact('registros', 'colaboradores', 'resumoNotificacoes'));
    }
}
