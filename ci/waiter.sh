#!/bin/bash
COMMIT=${1:0:10} # use first 10 digits of sha to reference
LOCK=/tmp/makebusy # Where to store lock files

if [ -z $COMMIT ]
then
	exit 1
fi

while [ -f $LOCK/$COMMIT ]
do
	sleep 30
done
