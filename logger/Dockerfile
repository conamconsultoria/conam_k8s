FROM webdevops/php:7.4
WORKDIR "/logger"

RUN apt-get update
RUN apt-get -y install librdkafka-dev
RUN pecl install rdkafka && docker-php-ext-enable rdkafka

COPY src/ /logger

ENTRYPOINT ["php","/logger/logger_daemon.php"]