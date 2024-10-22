FROM php:8.3-fpm

RUN apt update && apt install -y libpq-dev
RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip
RUN docker-php-ext-install pdo_mysql

RUN apt-get update \
	&& apt-get install -y \
		curl \
		git

# Composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
