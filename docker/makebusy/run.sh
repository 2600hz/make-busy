#!/bin/bash
if [ ! -z $1 ]
then
	VOLUME="-v $1:/home/user/make-busy/tests/KazooTests/Applications/"
else
	VOLUME=""
fi
NETWORK=${NETWORK:-"kazoo"}
NAME=makebusy.$NETWORK
docker stop -t 1 $NAME
docker rm -f $NAME
docker run -td \
	--net $NETWORK \
	-h $NAME \
	--name $NAME \
	$VOLUME \
	2600hz/makebusy

