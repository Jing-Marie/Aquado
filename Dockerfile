# Container image for Vercel's Container Runtime (or any Docker host).
# Serves the project root with FrankenPHP; see Caddyfile.
FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

# MySQL access for the apps (mysqli and PDO MySQL)
RUN install-php-extensions mysqli pdo_mysql

# Production PHP settings, and room for payment photos (up to 10 MB)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'upload_max_filesize = 10M\npost_max_size = 12M\n' > "$PHP_INI_DIR/conf.d/aquado.ini"

COPY . .

ENV PORT=80
CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
