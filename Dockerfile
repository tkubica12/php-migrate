FROM php:7.2-apache
RUN docker-php-ext-install pdo_mysql
COPY index.php /var/www/html/
COPY cacert.pem /cacert/pem