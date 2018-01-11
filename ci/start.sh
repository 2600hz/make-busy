#!/bin/sh
COMMIT=${1:0:10}
NETWORK=git-${COMMIT}
export NETWORK=${NETWORK}
export COMMIT=${COMMIT}
#     cd ~/docker-compose/mkbusy && docker-compose --no-ansi -p mkbusy-$COMMIT up -d
     cd ~/docker-compose/kazoo && COMMIT=$COMMIT docker-compose --no-ansi -p kazoo-$COMMIT up -d


