#!/bin/sh
if [ $DAO = "db" ]
then
	echo "{"                                	      			> /home/graf/configuration.json
	echo "  'dao' : 'db',               						>> /home/graf/configuration.json
	echo "  'db'  : {"                 		        			>> /home/graf/configuration.json
	echo "  		'host'     		: '$DB_HOST',"   			>> /home/graf/configuration.json
	echo "  		'user'     		: '$DB_USER',"   			>> /home/graf/configuration.json
	echo "  		'password' 		: '$DB_PASSWORD',"			>> /home/graf/configuration.json
	echo "  		'instance' 		: '$DB_INSTANCE'"			>> /home/graf/configuration.json
	echo "   }"                                  	 			>> /home/graf/configuration.json
	echo "}"                                      				>> /home/graf/configuration.json
elif [ $DAO = "itop" ]
	echo "{"                                      				> /home/graf/configuration.json
	echo "  'dao'  : 'itop',                      				>> /home/graf/configuration.json
	echo "  'itop' : {"                           				>> /home/graf/configuration.json
	echo "  		'url'      		: '$ITOP_URL',"    			>> /home/graf/configuration.json
	echo "  		'user'     		: '$ITOP_USER',"   			>> /home/graf/configuration.json
	echo "  		'password' 		: '$ITOP_PASSWORD'," 		>> /home/graf/configuration.json
	echo "  		'version'  		: '$ITOP_VERSION'," 		>> /home/graf/configuration.json
	echo "  		'organisation'  : '$ITOP_ORGANISATION'"		>> /home/graf/configuration.json
	echo "   }"                                   				>> /home/graf/configuration.json
	echo "}"                                      				>> /home/graf/configuration.json
else
	echo "ERROR : Mode non reconnu"
fi