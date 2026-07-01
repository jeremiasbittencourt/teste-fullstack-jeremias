#!/usr/bin/env bash
set -e

# Garante um .env (o APP_KEY é gerado nele; as demais configs vêm do ambiente
# do docker-compose, que tem precedência sobre o .env no Laravel).
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Espera o MySQL aceitar conexões antes de seguir.
echo "Aguardando MySQL em ${DB_HOST}:${DB_PORT}..."
until php -r '$c=@fsockopen(getenv("DB_HOST"),(int)getenv("DB_PORT")); exit($c?0:1);'; do
    sleep 2
done

# Espera o Redis.
echo "Aguardando Redis em ${REDIS_HOST}:${REDIS_PORT}..."
until php -r '$c=@fsockopen(getenv("REDIS_HOST"),(int)getenv("REDIS_PORT")); exit($c?0:1);'; do
    sleep 2
done

# Gera a APP_KEY se ainda não existir.
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

case "$1" in
    serve)
        php artisan migrate --force
        php artisan db:seed --force || true
        exec php artisan serve --host=0.0.0.0 --port=8000
        ;;
    worker)
        # Retenta cada job até 3x com backoff (requisito 4.3).
        exec php artisan queue:work --tries=3 --backoff=10 --sleep=3 --max-time=3600
        ;;
    *)
        exec "$@"
        ;;
esac
