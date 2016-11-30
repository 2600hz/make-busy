#!/bin/sh
REPO=${2:-"https://github.com/2600hz/make-busy.git"}
COMMIT=${1:-"HEAD"}
FLAGS=${3:-""}
echo Repo $COMMIT $REPO
echo $COMMIT > etc/commit

[ ! -f etc/config.json ] && echo kazoo.makebusy: etc/config.json does not exists, please use the sample config file && exit 1

docker build $FLAGS \
	--build-arg REPO=$REPO \
	--build-arg COMMIT=$COMMIT \
	-t kazoo/makebusy .
