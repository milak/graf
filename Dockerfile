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
RUN rm /var/www/html/index.html
ADD scripts/build_configuration.sh /home/graf
ADD scripts/run.sh /home/graf
RUN chmod a+x /home/graf/*.sh
ADD WebContent /var/www/html/

EXPOSE 80

# on veut une machine de dev qui affiche toutes les erreurs PHP
#RUN sed -i -e 's/^error_reporting\s*=.*/error_reporting = E_ALL/' /etc/php5/apache2/php.ini
#RUN sed -i -e 's/^display_errors\s*=.*/display_errors = On/' /etc/php5/apache2/php.ini

# commandes à exécuter au démarrage de l'instance de l'image
# ici on démarrera Apache
ENTRYPOINT ["/bin/bash", "/home/graf/run.sh"]
#CMD ["/home/graf/run.sh","-DFOREGROUND"]