FROM webdevops/php-nginx:7.4
ENV WEB_DOCUMENT_ROOT /app/www
RUN docker-cronjob '*/20 * * * * application /usr/local/bin/php /app/data.php > /app/cron.log 2>&1'
COPY ./ /app/
EXPOSE 80