#!/bin/sh
COMMIT=${1:0:10}

     cd ~/docker-compose/mkbusy && docker-compose --no-ansi -p mkbusy-$COMMIT down
     cd ~/docker-compose/kazoo && COMMIT=$COMMIT docker-compose --no-ansi -p kazoo-$COMMIT down


