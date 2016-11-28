#!/bin/bash

if [ ! -z $TESTS_PATH ] ; then
	VOLUME="-v $TESTS_PATH:/home/user/make-busy/tests/KazooTests/Applications/"
else
	VOLUME=""
fi

NETWORK=${NETWORK:-"kazoo"}
NAME=makebusy.$NETWORK

echo :: starting $NAME

docker stop -t 1 $NAME
docker rm -f $NAME
docker run -td \
	--net $NETWORK \
	-h $NAME \
	--name $NAME \
	$VOLUME \
	2600hz/makebusy

