# SISLOC ATENDIMENTO — Fase 1

Sistema de gerenciamento de chamados em **Laravel (PHP 8.3+)**, Blade, Tailwind CSS e Lucide, com nomenclatura 100% em português.
Este pacote é um *overlay*: contém `app/`, `database/`, `routes/`, `resources/`, `lang/` e `tests/` para serem aplicados sobre um Laravel 11/12 novo.

## Escopo desta entrega (Fase 1)

| Módulo | Situação |
|---|---|
| Autenticação (login, logout, recuperação e alteração de senha, controle de sessões, último acesso, foto, tema por usuário) | ✅ |
| RBAC (perfis, permissões, Gate + Policy, verificação em rotas e telas) | ✅ |
| Usuários (cadastro pelo administrador, ativar/desativar) | ✅ |
| Chamados (abertura, filtros, atribuição, transferência, prioridade, status, interações, notas internas, anexos protegidos, histórico, prazo/atraso) | ✅ |
| Finalização de atendimento (motivos administráveis, descrição da solução, registro automático) | ✅ |
| Departamentos, categorias/subcategorias, prioridades, status, motivos | ✅ |
| Notificações internas (contador, marcar como lida) | ✅ |
| Auditoria (somente leitura) | ✅ |
| Painel inicial com indicadores reais do banco | ✅ |
| Testes automatizados (autenticação, permissões, chamados, finalização) | ✅ |
| **IA** (resumo do usuário e sugestões) | ⏳ Fase 2 |
| **Dashboard gerencial** (gráficos, tempos médios, filtros) | ⏳ Fase 3 |
| **Construtor de Atendimento Automático** (fluxos visuais) | ⏳ Fase 4 |
| Configurações gerais, gestão de notificações e configuração da IA pela interface | ⏳ Fases 2–4 |

## Instalação

Pré-requisitos: PHP 8.3+, Composer, Node 20+, MySQL ou PostgreSQL.

```bash
./instalar.sh sisloc-atendimento
cd sisloc-atendimento
# configure DB_* no .env
php artisan migrate --seed
npm run dev          # terminal 1
php artisan serve    # terminal 2
```

Acesse http://localhost:8000.

## Credenciais de demonstração

Senha de todos: `Sisloc@2026` (o sistema exibe um aviso para trocá-la no primeiro acesso; **altere antes de qualquer uso real**).

| Perfil | E-mail |
|---|---|
| Administrador | admin@sisloc.test |
| Supervisor | supervisor@sisloc.test |
| Agente (Suporte) | agente@sisloc.test |
| Solicitante | marina@cliente.test |

## Testes

```bash
php artisan test
```

## Arquitetura

- `app/Models` — Eloquent (Usuario, Perfil, Permissao, Chamado, InteracaoChamado, FinalizacaoChamado…).
- `app/Services` — regras de negócio (`ChamadoService`, `AuditoriaService`, `NotificacaoService`).
- `app/Policies/ChamadoPolicy` + `Gate::before` em `AppServiceProvider` (habilidades `modulo.acao`).
- `app/Http/Requests` — Form Requests com mensagens em português.
- `app/Http/Controllers/Admin` — administração (usuários, perfis, cadastros, auditoria).
- Anexos são salvos em disco privado (`storage/app/private`) e baixados apenas por quem pode ver o chamado.

## Observações

- Os cadastros simples (departamentos, categorias, prioridades, status, motivos) usam um `CadastroController` orientado por definição de campos, para evitar cinco controllers idênticos.
- Os nomes `password`, `remember_token` e as chaves de sessão seguem convenções obrigatórias do Laravel.
- Este código foi escrito sem execução local de PHP no ambiente de geração: rode `php artisan test` e relate qualquer falha para ajuste.
