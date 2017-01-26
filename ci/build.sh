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

if [ -z $REPO ]
then
	SHA=$(curl -s https://api.github.com/repos/2600hz/kazoo/commits/$COMMIT | jq -r '.sha')
	REPO=2600hz:kazoo:$SHA
	echo Guessed repo: $REPO
fi

while [ -f /tmp/build.lock ]
do
	echo wait in queue for $(cat /tmp/build.lock)
	sleep 30
done

cd ~/make-busy/ci && php update-status.php $TOKEN $REPO pending

echo $COMMIT > /tmp/build.lock
export NETWORK=git-$COMMIT
docker network create $NETWORK
cd ~/kazoo-docker/kazoo && ./build-commit.sh $COMMIT

function stop_segment {
	docker logs kazoo.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/kazoo.log
	docker logs kamailio.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/kamailio.log
	docker logs freeswitch.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/freeswitch.log

	# Makebusy Post-Mortem (just in case)
	for fs in makebusy-fs-auth makebusy-fs-carrier makebusy-fs-pbx
	do
		docker logs $fs.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/$fs.log
		echo "Post-Mortem" >> ~/volume/log/$COMMIT/$fs.log
		docker exec $fs.$NETWORK bin/fs_cli -x "sofia status" 2>&1 >> ~/volume/log/$COMMIT/$fs.log
		TYPE=$(echo $fs | sed s/makebusy-fs-//)
		docker exec $fs.$NETWORK curl -s http://makebusy.$NETWORK/gateways.php/gateways.php?type=$TYPE >> ~/volume/log/$COMMIT/$fs.log
	done

	docker stop -t 2 $(docker ps -q -a --filter name=$COMMIT)
	docker network rm $NETWORK
	rm -f /tmp/build.lock
}

cd ~/kazoo-docker/rabbitmq && ./run.sh
cd ~/kazoo-docker/couchdb && ./run.sh -td kazoo/couchdb-mkbs
cd ~/kazoo-docker/kamailio && ./run.sh
cd ~/kazoo-docker/freeswitch && ./run.sh
cd ~/kazoo-docker/kazoo && ./run-commit.sh $COMMIT

if [ "$(docker ps -q --filter name=kazoo.$NETWORK)" = ""  ]
then
	echo No Kazoo image, exiting...
	stop_segment
	exit 1
fi

cd ~/make-busy/docker/makebusy-fs && ./run-all.sh

# need to wait for fs drone to start
echo Wait for FreeSwitch drones to start...
timeout --foreground 20 watch -g "docker logs makebusy-fs-auth.$NETWORK | grep 'FreeSWITCH Started'" > /dev/null
if [ $? -ne 0 ]
then
	echo Failure to start FreeSwitch drone, exiting...
	stop_segment
	exit $?
fi

cd ~/make-busy/docker/makebusy/kazoo && ./configure-for-makebusy.sh
if [ $? -ne 0 ]
then
	echo Failure to start Kazoo image, exiting...
	stop_segment
	exit 1
fi

echo -n "Reload acls: "
sup ecallmgr_maintenance reload_acls

echo Sanity check...
cd ~/make-busy/docker/makebusy/kazoo && ./sanity-check.sh

echo Building makebusy...
cd ~/make-busy/docker/makebusy
BUILD_FLAGS=-q ./build.sh $(git rev-parse HEAD)
if [ -d ~/volume ]
then
	TESTS_PATH=kazoo-ci ./run.sh
else
	TESTS_PATH=~/tests ./run.sh
fi

echo Reloading kamailio dispatcher...
docker exec kamailio.$NETWORK kamcmd dispatcher.reload

cd ~/tests

for file in kazoo freeswitch kamailio makebusy-fs-auth makebusy-fs-pbx makebusy-fs-carrier run
do
	echo "Remove old log file $file"
	rm -f ~/volume/log/$COMMIT/$file.log
done

echo Starting tests...
mkdir -p ~/volume/log/$COMMIT

DIR=~/tests/Callflow
PREFIX=Callflow

TESTS=$(ls $DIR)
for FILE in $TESTS
do
   if [ -d $DIR/$FILE ]
   then
		LOG_CONSOLE=1 run-suite.sh $PREFIX/$FILE 2>> ~/volume/log/$COMMIT/suite.log | tee -a ~/volume/log/$COMMIT/run.log
		CLEAN=1 LOG_CONSOLE=1 run-test.sh $PREFIX/IncomingTestCase.php
   fi
done

stop_segment

if grep -q 'GIVE UP SUITE' ~/volume/log/$COMMIT/run.log
then
	echo SET ERROR STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO error
	exit 1
fi

if grep -q 'COMPLETE SUITE' ~/volume/log/$COMMIT/run.log
then
	echo SET SUCCESS STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO success
	exit 0
fi

echo SET FAILURE STATUS > /dev/null
cd ~/make-busy/ci && php update-status.php $TOKEN $REPO failure
exit 2
