#!/bin/bash
if [ ! -z $1 ]
then
	VOLUME="-v $1:/var/www/html/make-busy/tests/KazooTests/Applications/"
else
	VOLUME=""
fi
NETWORK=${NETWORK:-"kazoo"}
NAME=makebusy.$NETWORK
docker stop $NAME
docker rm $NAME
docker run -td \
	--net $NETWORK \
	-h $NAME \
	--name $NAME \
	$VOLUME \
	kazoo/makebusy

