#!/bin/sh
/home/graf/build_configuration.sh
if [ $? -eq 0 ]
then
	/usr/sbin/apache2ctl -DFOREGROUND
fi