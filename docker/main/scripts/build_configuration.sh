#!/bin/bash
if [ -z "$DAO" ]
then
	echo "Missing \$DAO env variable";
	exit 2
elif [ $DAO = "itop" ]
then
	echo "{"                                      					>  /home/graf/configuration.json
	echo "  \"dao\"  : \"itop\","                      				>> /home/graf/configuration.json
	echo "  \"itop\" : {"                           				>> /home/graf/configuration.json
	echo "  		\"url\"      		: \"$ITOP_URL\","    		>> /home/graf/configuration.json
	echo "  		\"login\"    		: \"$ITOP_LOGIN\","  		>> /home/graf/configuration.json
	echo "  		\"password\" 		: \"$ITOP_PASSWORD\"," 		>> /home/graf/configuration.json
	echo "  		\"version\"  		: \"$ITOP_VERSION\"," 		>> /home/graf/configuration.json
	echo "  		\"organisation\"	: \"$ITOP_ORGANISATION\""	>> /home/graf/configuration.json
	echo "   }"                                   					>> /home/graf/configuration.json
	echo "}"                                      					>> /home/graf/configuration.json
	chmod a+w /home/graf/configuration.json 
elif [ $DAO = "db" ]
then
	echo "{"                                	      			>  /home/graf/configuration.json
	echo "  \"dao\" : \"db\","               					>> /home/graf/configuration.json
	echo "  \"db\"  : {"                 		        		>> /home/graf/configuration.json
	echo "  		\"host\"     		: \"$DB_HOST\","   		>> /home/graf/configuration.json
	echo "  		\"login\"    		: \"$DB_LOGIN\","   	>> /home/graf/configuration.json
	echo "  		\"password\" 		: \"$DB_PASSWORD\","	>> /home/graf/configuration.json
	echo "  		\"instance\" 		: \"$DB_INSTANCE\""		>> /home/graf/configuration.json
	echo "   }"                                  	 			>> /home/graf/configuration.json
	echo "}"                                      				>> /home/graf/configuration.json
	chmod a+w /home/graf/configuration.json
else
	echo "ERROR : Mode '$DAO' non reconnu"
	exit 2
fi