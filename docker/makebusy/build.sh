#!/bin/sh
REPO=${2:-"https://github.com/2600hz/make-busy.git"}
COMMIT=${1:-"HEAD"}
FLAGS=${3:-""}
echo Using repository $REPO commit $COMMIT
echo $COMMIT > etc/commit
docker build $FLAGS \
	--build-arg REPO=$REPO \
	--build-arg COMMIT=$COMMIT \
	-t 2600hz/makebusy .
