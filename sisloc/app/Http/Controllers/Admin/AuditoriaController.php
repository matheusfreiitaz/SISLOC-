<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
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

        return view('admin.auditoria', compact('registros'));
    }
}
