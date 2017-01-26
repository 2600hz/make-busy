#!/bin/bash

NETWORK=${NETWORK:-"kazoo"}
NAME=makebusy.$NETWORK

if [ ! -z $TESTS_PATH ]
then
	VOLUME="-v $TESTS_PATH:/home/user/make-busy/tests/KazooTests/Applications/"
else
	echo Probably useless, please specify where are your tests in TESTS_PATH env variable
	VOLUME=""
fi

if [ -n "$(docker ps -aq -f name=$NAME)" ]
then
   echo -n "stopping: "
   docker stop -t 1 $NAME
   echo -n "removing: "
   docker rm -f $NAME
fi

echo -n "starting: $NAME tests: $TESTS_PATH "
docker run -td \
	--net $NETWORK \
	-h $NAME \
	--name $NAME \
	-e NETWORK=$NETWORK \
	$VOLUME \
	kazoo/makebusy

