#!/bin/bash
# here we assume specific folder structure:
# $HOME/kazoo-docker
# $HOME/make-busy
# $HOME/tests
export PATH=$PATH:~/kazoo-docker/kazoo:~/make-busy/bin
COMMIT=${1:0:10}
export NETWORK=git-$COMMIT
docker network create $NETWORK
cd ~/kazoo-docker/kazoo
./build-commit.sh $COMMIT
cd ~/kazoo-docker/rabbitmq && ./run.sh
cd ~/kazoo-docker/couchdb && ./run.sh
cd ~/kazoo-docker/kamailio && ./run.sh
cd ~/kazoo-docker/freeswitch && ./run.sh
cd ~/kazoo-docker/kazoo
./run-commit.sh $COMMIT
cd ~/kazoo-docker
./after-start.sh
cd ~/make-busy/docker/makebusy-fs
./run-all.sh
cd ~/make-busy/docker/makebusy
./make-config.sh $NETWORK
./build.sh
if [ -d ~/volume ]
then
	TESTS_PATH=kazoo-ci ./run.sh
else
	TESTS_PATH=~/tests ./run.sh
fi
cd ~/make-busy/docker/makebusy/kazoo/
./configure-for-makebusy.sh
./setup-makebusy-prompts.sh
cd ~/tests
mkdir log
echo RUN SUITE
run-suite.sh Callflow | tee -a log/$COMMIT
echo SUITE EXIT CODE: $?"
docker stop $(docker ps -q -a --filter name=$COMMIT)
