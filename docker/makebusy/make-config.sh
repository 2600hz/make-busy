#!/bin/bash
NETWORK=$1
if [ -z $NETWORK ]
then
	echo Please specify the network segment to run tests in
	exit
fi
cp etc/config.json.dist etc/config.json
/bin/sed -i "s/\.kazoo/\.$NETWORK/g" etc/config.json
