#installe PHP 8.2
FROM php:8.2-apache
ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions \
	&& sync \
	&& install-php-extensions gd
RUN apt-get update && apt-get install -y libicu-dev \
        && docker-php-ext-install mysqli pdo_mysql intl

ADD ./docker/custom-php.ini /usr/local/etc/php/conf.d/php-custom.ini

#installe composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer
   
#installe symfony   
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install -y symfony-cli

#copie les fichiers du projet sur le container
COPY . /var/www/html/
    
#copie le fichier de config d'apache sur le container
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

#répertoire de travail par défaut au lancement du bash php
WORKDIR /var/www/html/

#Donne les permissions à l'utilisateur www-data avec l'id 1000
RUN usermod -u 1000 www-data
RUN chown -R www-data:www-data var/cache
RUN chown -R www-data:www-data var/log
RUN chown -R www-data:www-data ./public

EXPOSE 80




