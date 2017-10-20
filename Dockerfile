FROM uppdragshuset/php7nginx:latest

RUN mkdir -p /app
WORKDIR /app
ADD . /app
COPY .env.example .env
RUN chmod -R 0777 /app/storage

RUN apt-get update
RUN apt-get install -y --force-yes supervisor

ADD laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

# install composer again to trigger scripts.. this should be refactored
RUN composer install --no-scripts --no-interaction --prefer-dist --no-dev
RUN php artisan key:generate

RUN chmod a+x docker-entrypoint.sh
CMD ["/app/docker-entrypoint.sh"]
