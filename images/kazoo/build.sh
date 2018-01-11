#!/bin/sh
COMMIT=${1:-master}

#docker build -t 2600hz/kazoo:$COMMIT --target kazoo --build-arg COMMIT=$COMMIT .

docker build -q --target=build -t 2600hz/kazoo-build:$COMMIT --build-arg COMMIT=$COMMIT .
docker build -q --target=kazoo -t 2600hz/kazoo:$COMMIT --build-arg COMMIT=$COMMIT --squash --force-rm --label kazoo.commit=$COMMIT .
docker rmi 2600hz/kazoo-build:$COMMIT 
docker rmi $(docker image ls -f dangling=true -f label=kazoo.commit=$COMMIT -q) --force
