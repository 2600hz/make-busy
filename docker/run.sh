#!/bin/sh
NETWORK=${1:-"kazoo"}
echo Starting network: $NETWORK
export $NETWORK
docker network create $NETWORK
cd makebusy-fs
./run-all.sh
cd ../makebusy
./run.sh
