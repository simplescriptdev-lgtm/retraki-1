FROM php:8.2-cli

# SQLite headers + інструменти для складання
RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-0 \
    libsqlite3-dev \
    pkg-config \
    build-essential \
 && docker-php-ext-configure pdo_sqlite --with-pdo-sqlite \
 && docker-php-ext-install -j$(nproc) pdo_sqlite \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

ENV PORT=10000

# каталог для даних (під диском Render, якщо підключите)
RUN mkdir -p /var/data && chown -R www-data:www-data /var/data

# якщо index.php у корені:
CMD php -S 0.0.0.0:${PORT} -t .
# якщо у /public — замініть рядок вище на:
# CMD php -S 0.0.0.0:${PORT} -t public
