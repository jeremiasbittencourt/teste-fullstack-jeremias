# Imagem única usada pelos serviços "app" (API) e "worker" (queue:work).
FROM php:8.3-cli AS base

# Extensões: pdo_mysql (banco), pcntl (sinais do queue:work), pdo_sqlite (testes).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libsqlite3-dev unzip git \
    && docker-php-ext-install pdo_mysql pdo_sqlite pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências primeiro (melhor cache de camadas).
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# Código da aplicação.
COPY . .
RUN composer dump-autoload --optimize --no-interaction

# Usuário não-root (requisito de segurança para produção).
RUN useradd -u 1000 -m appuser \
    && chown -R appuser:appuser /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER appuser

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["serve"]
