#!/bin/sh
COMMIT=$1
export NETWORK=$COMMIT
cd ~/kazoo-docker/kazoo
./build-commit.sh $COMMIT
cd ~/kazoo-docker
rabbitmq/run.sh
couchdb/run.sh
kamailio/run.sh
freeswitch/run.sh
kazoo/run-commit.sh $COMMIT
cd ~/make-busy/docker/makebusy-fs
./run-all.sh
cd ~/make-busy/docker/makebusy
TESTS_PATH=~/tests ./run.sh
cd ~/make-busy
bin/run-suite.sh Callflow
