#!/bin/sh
COMMIT=${1:-"HEAD"}
REPO=${REPO:-"https://github.com/2600hz/make-busy.git"}
echo repo: $COMMIT $REPO
mkdir -p etc
echo $COMMIT > etc/commit

docker build $BUILD_FLAGS \
	--build-arg REPO=$REPO \
	-t kazoo/makebusy .
