#!/bin/sh
echo "{"                                           > /home/graf/configuration.json
echo "  'db' : {"                                  >> /home/graf/configuration.json
echo "  		'host'     : '$GRAF_DB_HOST',"     >> /home/graf/configuration.json
echo "  		'user'     : '$GRAF_DB_USER',"     >> /home/graf/configuration.json
echo "  		'password' : '$GRAF_DB_PASSWORD'," >> /home/graf/configuration.json
echo "  		'instance' : '$GRAF_DB_INSTANCE'," >> /home/graf/configuration.json
echo "   }"                                        >> /home/graf/configuration.json
echo "}"                                           >> /home/graf/configuration.json