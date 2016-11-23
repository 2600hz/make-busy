#!/bin/sh
REPO=${2:-"https://github.com/2600hz/make-busy.git"}
COMMIT=${1:-$(../bin/get-commit $REPO)}
FLAGS=${3:-""}
cp -a ~/.ssh etc/
echo Using repository $REPO commit $COMMIT
echo $COMMIT > etc/commit
docker build $FLAGS \
	--build-arg REPO=$REPO \
	--build-arg COMMIT=$COMMIT \
	-t kazoo/makebusy .
