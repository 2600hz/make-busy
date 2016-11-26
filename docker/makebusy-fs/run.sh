#!/bin/sh
TYPE=${1:-"auth"}
FLAGS=${2:-"-td"}
NETWORK=${NETWORK:-"kazoo"}
MAKEBUSY_CONTAINER=${3:-"makebusy.$NETWORK"}
NAME=makebusy-fs-$TYPE.$NETWORK

echo :: starting $NAME instance

docker stop -t 1 $NAME
docker rm -f $NAME
docker run $FLAGS \
	--restart unless-stopped \
	--net $NETWORK \
	-h $NAME \
	--name $NAME \
	--env TYPE=$TYPE \
	--env MAKEBUSY_URL=http://$MAKEBUSY_CONTAINER/gateways.php \
	2600hz/makebusy-fs
