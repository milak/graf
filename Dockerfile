FROM ubuntu:latest

MAINTAINER Milak <milak@github.com>

LABEL version="1.0"
LABEL description="Apache 2 / PHP / GRAF"

RUN apt-get -y update && apt-get install -y \
apache2 \
php \
libapache2-mod-php \
php-json \
php-mysql

RUN mkdir /home/graf
ADD model/views /home/graf
RUN rm /var/www/html/index.html
ADD docker/main/scripts/build_configuration.sh /home/graf
ADD docker/main/scripts/run.sh /home/graf
RUN chmod a+rw /home/graf
RUN chmod a+x /home/graf/*.sh
ADD WebContent /var/www/html/

EXPOSE 80

# commandes à exécuter au démarrage de l'instance de l'image
ENTRYPOINT ["/bin/bash", "/home/graf/run.sh"]