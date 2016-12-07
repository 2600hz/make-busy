#!/bin/bash
# here we assume specific folder structure:
# $HOME/kazoo-docker
# $HOME/make-busy
# $HOME/tests
export PATH=$PATH:~/kazoo-docker/kazoo:~/make-busy/bin
COMMIT=${1:0:10}
REPO=$2
export NETWORK=git-$COMMIT
docker network create $NETWORK
cd ~/kazoo-docker/rabbitmq && ./run.sh
cd ~/kazoo-docker/couchdb && ./run.sh
cd ~/kazoo-docker/kamailio && ./run.sh
cd ~/kazoo-docker/freeswitch && ./run.sh
cd ~/kazoo-docker/kazoo
./build-commit.sh $COMMIT
./run-commit.sh $COMMIT
cd ~/kazoo-docker
./after-start.sh
cd ~/make-busy/docker/makebusy-fs
./run-all.sh
cd ~/make-busy/docker/makebusy
./run.sh
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
mkdir -p log
run-suite.sh Callflow | tee -a log/$COMMIT
docker stop $(docker ps -q -a --filter name=$COMMIT)

grep 'GIVE UP SUITE' log/$COMMIT
if [ $? -eq 0 ]
then
	echo SET ERROR STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO error
	exit 1
fi

grep 'COMPLETE SUITE' log/$COMMIT
if  [ $? -eq 0 ]
then
	echo SET SUCCESS STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO success
	exit 0
fi

echo SET FAILURE STATUS
cd ~/make-busy/ci && php update-status.php $TOKEN $REPO failure
exit 2
