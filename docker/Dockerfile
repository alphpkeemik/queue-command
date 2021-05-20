FROM phpdockerio/php74-fpm


RUN apt-get update
RUN apt-get install -y git php7.4-sqlite

#RUN apt-get install -y php7.2-common php7.2-curl php7.2-mysql php7.2-mbstring php7.2-zip php7.2-xml php7.2-soap php7.2-bcmath php7.2-xdebug

# install composer
RUN curl --silent --show-error https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer


WORKDIR /var/www/html
