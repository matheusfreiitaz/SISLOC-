#!/usr/bin/env bash
# Instala o SISLOC ATENDIMENTO sobre um projeto Laravel 11/12 novo.
# Uso:  ./instalar.sh nome-do-projeto
set -euo pipefail
ORIGEM="$(cd "$(dirname "$0")" && pwd)"
DESTINO="${1:-sisloc-atendimento}"

composer create-project laravel/laravel "$DESTINO"
cd "$DESTINO"

# Remove a estrutura padrão que é substituída pelo módulo em português
rm -f app/Models/User.php database/factories/UserFactory.php tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
rm -f database/migrations/0001_01_01_000000_create_users_table.php

# Copia o código do projeto
cp -R "$ORIGEM"/app "$ORIGEM"/database "$ORIGEM"/lang "$ORIGEM"/resources "$ORIGEM"/routes "$ORIGEM"/tests .

# Configuração de ambiente
touch .env
for linha in "AUTH_MODEL=App\\\\Models\\\\Usuario" "APP_LOCALE=pt_BR" "APP_FALLBACK_LOCALE=pt_BR" "APP_FAKER_LOCALE=pt_BR" "SESSION_DRIVER=database"; do
  chave="${linha%%=*}"
  if grep -q "^${chave}=" .env; then sed -i "s|^${chave}=.*|${linha}|" .env; else echo "$linha" >> .env; fi
done
sed -i "s|'timezone' => 'UTC'|'timezone' => 'America/Sao_Paulo'|" config/app.php
cat "$ORIGEM/.env.sisloc" >> .env.example

php artisan key:generate
php artisan storage:link
npm install
echo
echo "Pronto. Configure o banco no .env (MySQL/PostgreSQL) e execute:"
echo "  php artisan migrate --seed"
echo "  npm run dev   (em um terminal)  /  php artisan serve  (em outro)"
