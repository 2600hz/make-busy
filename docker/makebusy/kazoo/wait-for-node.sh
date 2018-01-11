#!/bin/bash
NODE=${1:-"kazoo"}
TIMEOUT=${2:-"120"}
NETWORK=${NETWORK:-"kazoo"}

#echo Waiting for $NODE.$NETWORK to start
#timeout --foreground $TIMEOUT watch -g "docker logs $NODE.$NETWORK | grep 'auto-started kapps'" > /dev/null
if ! /home/user/make-busy/docker/makebusy/kazoo/t.sh $NODE.$NETWORK $TIMEOUT "auto-started kapps"; then
   exit 1
else
   exit 0
fi

