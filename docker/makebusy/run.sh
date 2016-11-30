#!/bin/bash

if [ ! -z $TESTS_PATH ] ; then
	VOLUME="-v $TESTS_PATH:/home/user/make-busy/tests/KazooTests/Applications/"
else
	echo Probably useless, please specify where are your tests in TESTS_PATH env variable
	VOLUME=""
fi

NETWORK=${NETWORK:-"kazoo"}
NAME=makebusy.$NETWORK

echo :: starting $NAME tests:$TESTS_PATH

docker stop -t 1 $NAME
docker rm -f $NAME
docker run -td \
	--net $NETWORK \
	-h $NAME \
	--name $NAME \
	$VOLUME \
	kazoo/makebusy

