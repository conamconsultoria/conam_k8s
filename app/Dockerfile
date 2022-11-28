FROM phpdockerio/php:7.4-fpm
WORKDIR "/app"

RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        php7.4-bcmath \ 
        php7.4-bz2 \ 
        php7.4-gd \ 
        php7.4-pgsql \ 
        php7.4-redis \ 
        php7.4-soap; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*