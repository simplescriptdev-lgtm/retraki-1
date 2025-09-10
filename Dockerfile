# Легкий образ з PHP 8.2
FROM php:8.2-cli

# Встановимо потрібні розширення (pdo_sqlite за потреби)
RUN docker-php-ext-install pdo pdo_sqlite

WORKDIR /app
COPY . /app

# Порт Render передає як змінну PORT
ENV PORT=10000

# За потреби – створимо каталог даних (якщо не змонтовано диск)
RUN mkdir -p /var/data && chown -R www-data:www-data /var/data

# Якщо у тебе є папка "public" — серверимо її;
# інакше заміни "-t public" на "-t ."
CMD php -S 0.0.0.0:${PORT} -t public
