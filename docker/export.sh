#!/bin/bash
export NETWORK=${NETWORK:-"kazoo"}
cd makebusy/export
./extract.sh
./build.sh
cd ../../makebusy-fs
./build.sh
docker push 2600hz/makebusy
docker push 2600hz/makebusy-fs
