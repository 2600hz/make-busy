#!/bin/sh
if [ -z $1 ]
then
	echo Please specify the test file relatively to your tests folder
	exit
fi
docker exec -ti makebusy.kazoo ./run-test.sh tests/KazooTests/Applications/$1
