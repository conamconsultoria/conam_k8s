FROM webdevops/php-apache:7.4
WORKDIR "/app"

RUN apt-get update
RUN apt-get -y install librdkafka-dev
RUN pecl install rdkafka && docker-php-ext-enable rdkafka

COPY src/ /app