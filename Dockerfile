# Легкий образ з PHP 8.2
FROM php:8.2-cli

# Встановлюємо потрібні пакети і розширення
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

# Порт Render передає як змінну PORT
ENV PORT=10000

# Каталог для SQLite/файлів (якщо є змонтований диск)
RUN mkdir -p /var/data && chown -R www-data:www-data /var/data

# Якщо index.php у корені:
CMD php -S 0.0.0.0:${PORT} -t .
# Якщо index.php у public/, тоді заміни:
# CMD php -S 0.0.0.0:${PORT} -t public
