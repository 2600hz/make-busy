#!/bin/bash
NETWORK=$1
if [ -z $NETWORK ]
then
	echo Please specify the network segment to run tests in
	exit
fi
cp etc/config.json.dist config.json
/bin/sed -i "s/\.kazoo/\.$NETWORK/g" config.json
