<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## SISLOC Atendimento

The existing support application is extended in place; its ticket, category, department, permission, notification, audit, interaction, and private-attachment structures remain the source of truth.

- Customer request portal: `/abrir-chamado`. Requests are created directly in the existing ticket queue, use the configured active fields, and receive a unique protocol.
- Customer follow-up: a random, individual link is sent to the supplied e-mail. Only its SHA-256 hash is stored; the link expires after 90 days. Staff with ticket-edit permission can revoke active links.
- Public form configuration: **Administration → Configuração do formulário**, protected by `formulario.configurar`. The administrator can assign that permission to other profiles through the existing profile-permission editor.
- Queue and completed history: **Fila de Atendimento** and **Atendimentos Finalizados** are filtered by the current user's ticket visibility. CSV export is separately controlled by `finalizados.exportar`.
- Transfers and chat messages reuse the ticket history, interaction, notification, audit, and private attachment records. Conversations refresh in the browser every five seconds; internal notes are never returned by customer-facing routes.
- Public uploads are restricted to administrator-approved image, PDF, Office, text, and CSV extensions, stored on the private disk, and limited to 10 MB per file by default (five files per submission/message).
- The access screen separates **Sou cliente** (guided chat and secure individual follow-up/history links) from **Sou funcionário** (internal sign-in). Internal access requires the `fila.ver` permission; customer accounts without that permission cannot enter the employee workspace.

Apply the incremental schema update with `php artisan migrate`; do not use `migrate:fresh` on an existing installation. Run regression tests with `php artisan test`.

### Monitoramento automático de chamados

Administradores com permissão para gerenciar cadastros podem configurar o monitoramento em **Administração → Monitoramento**. A rotina identifica chamados sem responsável, em espera prolongada, sem atualização, pendentes ou aguardando o usuário além dos limites definidos, além de alertar sobre SLA próximo ou vencido. Alertas ativos não geram uma nova notificação a cada execução; um novo aviso é enviado quando uma ocorrência começa ou volta a ocorrer. O monitoramento não altera o status dos chamados.

O intervalo padrão é de 20 minutos, todos os dias, durante todo o dia. O Laravel Scheduler consulta as configurações a cada minuto e executa a análise quando o intervalo e a janela configurados forem atendidos. Em desenvolvimento, mantenha `php artisan schedule:work` em execução. Em produção Linux, configure o cron para executar `php artisan schedule:run` a cada minuto; no Windows, configure uma tarefa agendada para executar o mesmo comando a cada minuto, com o diretório de trabalho apontando para a aplicação.

Use `php artisan schedule:list` para confirmar o agendamento. As execuções, falhas, quantidades analisadas e alertas ativos ficam disponíveis na tela de monitoramento. `php artisan atendimento:monitorar --force` executa uma verificação imediata, ignorando intervalo e janela de funcionamento.
