#!/bin/bash
# script assumes specific folder structure:
# $HOME/kazoo-docker
# $HOME/make-busy
# $HOME/tests

export PATH=$PATH:~/kazoo-docker/kazoo:~/make-busy/bin
COMMIT=${1:0:10}
REPO=$2
if [ -z $COMMIT ]
then
	echo Usage: $0 commit_ref repo_ref
	exit 1
fi

while [ -f /tmp/build.lock ]
do
	echo wait in queue for $(cat /tmp/build.lock)
	sleep 30
done

export NETWORK=git-$COMMIT
docker network create $NETWORK

function stop_segment {
	docker logs kazoo.$NETWROK > ~/tests/log/$COMMIT/kazoo.log
	docker logs kamailio.$NETWROK > ~/tests/log/$COMMIT/kamailio.log
	docker logs freeswitch.$NETWROK > ~/tests/log/$COMMIT/freeswitch.log
	docker stop -t 2 $(docker ps -q -a --filter name=$COMMIT)
	docker network rm $NETWORK
	rm -f /tmp/build.lock
}

cd ~/kazoo-docker/rabbitmq && ./run.sh
cd ~/kazoo-docker/couchdb && ./run.sh -td kazoo/couchdb-mkbs
cd ~/kazoo-docker/kamailio && ./run.sh
cd ~/kazoo-docker/freeswitch && ./run.sh

cd ~/kazoo-docker/kazoo
./build-commit.sh $COMMIT
./run-commit.sh $COMMIT

if [ "$(docker ps -q --filter name=kazoo.$NETWORK)" = ""  ]
then
	echo No Kazoo image, exiting...
	stop_segment
	exit 1
fi

cd ~/make-busy/docker/makebusy/kazoo

./configure-for-makebusy.sh
if [ $? -ne 0 ]
then
	echo Failure to start Kazoo image, exiting...
	stop_segment
	exit 1
fi

cd ~/make-busy/docker/makebusy-fs
./run-all.sh

# need to wait for fs drone to start
echo Wait for FreeSwitch drones to start
timeout --foreground 20 watch -g "docker logs makebusy-fs-auth.$NETWORK | grep 'FreeSWITCH Started'" > /dev/null
if [ $? -ne 0 ]
then
	echo Failure to start FreeSwitch drone, exiting...
	stop_segment
	exit $?
fi

cd ~/make-busy/docker/makebusy
./build.sh $(git rev-parse HEAD)
if [ -d ~/volume ]
then
	TESTS_PATH=kazoo-ci ./run.sh
else
	TESTS_PATH=~/tests ./run.sh
fi

cd ~/tests

mkdir -p log/$COMMIT
rm -f log/$COMMIT/suite.log
run-suite.sh Callflow | tee -a log/$COMMIT/suite.log
grep TEST\|SUITE log/$COMMIT/suite.log > log/$COMMIT/run.log

stop_segment

if [ -z $REPO ]
then
	SHA=$(cd /tmp && rm -rf kazoo && git clone -q https://github.com/2600hz/kazoo && cd kazoo && git rev-parse $COMMIT && cd ../ && rm -rf kazoo)
	REPO=2600hz:kazoo:$SHA
	echo Guessed repo: $REPO
fi

if grep -q 'GIVE UP SUITE' log/$COMMIT/suite.log
then
	echo SET ERROR STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO error
	exit 1
fi

if grep -q 'COMPLETE SUITE' log/$COMMIT/suite.log
then
	echo SET SUCCESS STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO success
	exit 0
fi

echo SET FAILURE STATUS > /dev/null
cd ~/make-busy/ci && php update-status.php $TOKEN $REPO failure
exit 2
