#!/bin/sh
COMMIT=${1:-"HEAD"}
REPO=${2:-"https://github.com/2600hz/make-busy.git"}
FLAGS=${3:-""}
echo repo: $COMMIT $REPO
mkdir -p etc
echo $COMMIT > etc/commit

docker build $FLAGS \
	--build-arg REPO=$REPO \
	-t kazoo/makebusy .
