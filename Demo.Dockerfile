FROM ubuntu:latest

MAINTAINER Milak <milak@github.com>

LABEL version="1.0"
LABEL description="Apache 2 / PHP / GRAF / ITOP"

RUN apt-get -y update

RUN apt-get install -y \
apache2 \
php \
libapache2-mod-php \
php-json \
php-mysql

# Install Mysql
RUN DEBIAN_FRONTEND=noninteractive apt-get -yq install mysql-server
RUN mkdir -p /var/run/mysqld
RUN chown mysql:mysql /var/run/mysqld

RUN rm /var/www/html/index.html

# Install itop
# - ses dépendances
RUN apt-get install -y \
unzip \
php-xml \
php-zip \
php-soap \
php-gd \
php-mcrypt \
php-ldap \
graphviz
# - le programme en lui-même
ADD docker/demo/resources/iTop-2.4.0-3585.zip /tmp
RUN unzip /tmp/iTop-2.4.0-3585.zip -d /tmp/itop
RUN mv /tmp/itop/web /var/www/html/itop
RUN chmod -R a+w /var/www/html/itop
RUN rm /tmp/iTop-2.4.0-3585.zip
# Install graf
RUN mkdir /home/graf
ADD model/views /home/graf
ADD docker/demo/conf/configuration.json /home/graf
ADD docker/demo/scripts/run.sh /home/graf
RUN chmod a+rw /home/graf
RUN chmod a+rw /home/graf/configuration
RUN chmod a+x /home/graf/*.sh
RUN mkdir /var/www/html/graf
ADD WebContent /var/www/html/graf

EXPOSE 80

# commandes à exécuter au démarrage de l'instance de l'image
ENTRYPOINT ["/bin/bash", "/home/graf/run.sh"]