#installe PHP 8.2
FROM php:8.2-apache
RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

#configure le fichier php-ini    
ADD ./docker/custom-php.ini /usr/local/etcf/php/conf.d/custom-php.ini

#installe composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer
   
#installe symfony   
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install -y symfony-cli

#installe node
RUN apt update && apt install -yqq nodejs npm

#copie les fichiers du projet sur le container
COPY . /var/www/

#copie le fichier de config d'apache sur le container
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

#répertoire de travail par défaut au lancement du bash php
WORKDIR /var/www/

EXPOSE 80




