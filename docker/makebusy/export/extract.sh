#!/bin/sh
CONTAINER=${1:-"makebusy.kazoo"}
rm -rf makebusy
rm -rf makebusy.tar
mkdir makebusy
docker cp $CONTAINER:/home/user makebusy
rm -rf makebusy/.git
find ./makebusy -name .git -exec rm -rf {} \; 2>/dev/null
cd makebusy && tar cf ../makebusy.tar ./ --owner=1000 --group=1000 && cd ../
rm -rf makebusy
