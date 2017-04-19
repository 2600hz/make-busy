#!/bin/bash
# This script assumes following folder structure:
# $HOME/kazoo-docker
# $HOME/make-busy
# $HOME/tests
export PATH=$PATH:~/kazoo-docker/kazoo:~/make-busy/bin

# Command line arguments
COMMIT=${1:0:10} # use first 10 digits of sha to reference
REPO_REF=$2 # format: owner:name:commit, comes from pull-request.php, used to set statuses

# Configuration
PARALLEL=${PARALLEL:-"4"}
LOCK=/tmp/makebusy # Where to store lock files

# Beware: overridable global variables
BRANCH=${BRANCH:-""}
PR=${PR:-""} # only to guess REPO_REF, see action.php
KZ_BUILD_FLAGS=${KZ_BUILD_FLAGS:-""} # comes from action.php, to alter kazoo build
TOKEN=${TOKEN} # Github access token, supposedly set as container global

if [ -z $COMMIT ]
then
	echo Usage: $0 commit_ref repo_ref
	exit 1
fi

mkdir -p $LOCK

# Wait if PR is updated before this run ends
while [ -f $LOCK/$COMMIT ]
do
	sleep 30
done

if [ -z $REPO_REF ]
then
	SHA=$(curl -s https://api.github.com/repos/2600hz/kazoo/commits/$COMMIT | jq -r '.sha')
	REPO_REF=2600hz:kazoo:$SHA:$PR
	echo Guessed repo: $REPO_REF
fi

cd ~/make-busy/ci && php update-status.php $TOKEN $REPO_REF pending

while [ $(ls -1 $LOCK | wc -l) -gt $PARALLEL ]
do
	echo wait in queue for $(ls $LOCK)
	sleep 30
done

for file in kazoo freeswitch kamailio makebusy-fs-auth makebusy-fs-pbx makebusy-fs-carrier run suite makebusy
do
	rm -f ~/volume/log/$COMMIT/$file.log
done

touch $LOCK/$COMMIT

export NETWORK=git-$COMMIT
docker network create $NETWORK
echo Build Kazoo commit:$COMMIT branch:$BRANCH
cd ~/kazoo-docker/kazoo && BUILD_FLAGS="-q $KZ_BUILD_FLAGS" BRANCH=$BRANCH ./build.sh $COMMIT
function stop_segment {
	docker logs kazoo.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/kazoo.log
	docker logs kamailio.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/kamailio.log
	docker logs freeswitch.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/freeswitch.log
	docker logs rabbitmq.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/rabbitmq.log
	docker logs couchdb.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/couchdb.log
	docker logs makebusy.$NETWORK | ~/kazoo-docker/bin/uncolor > ~/volume/log/$COMMIT/makebusy.log

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
	rm -f $LOCK/$COMMIT
}

cd ~/kazoo-docker/rabbitmq && ./run.sh
cd ~/kazoo-docker/couchdb && ./run.sh -td kazoo/couchdb-mkbs
cd ~/kazoo-docker/kamailio && ./run.sh
cd ~/kazoo-docker/freeswitch && ./run.sh
cd ~/kazoo-docker/kazoo && ./run.sh

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

echo Starting tests...
mkdir -p ~/volume/log/$COMMIT
TESTS=~/tests
cd $TESTS
for APP in $(ls $TESTS)
do
	if [ -d $APP ]
	then
		for CASE in $(ls $APP)
		do
			if [ -d $APP/$CASE ]
			then
				LOG_CONSOLE=1 run-suite.sh $APP/$CASE 2>> ~/volume/log/$COMMIT/suite.log | tee -a ~/volume/log/$COMMIT/run.log 
				CLEAN=1 SKIP_ACCOUNT=1 run-test.sh ../EmptyTestCase.php
			fi
		done
	fi
done

stop_segment

cp ~/volume/log/$COMMIT/run.log ~/volume/log/$COMMIT/run.log.tmp
cat ~/volume/log/$COMMIT/run.log.tmp | grep -P TEST\|SUITE > ~/volume/log/$COMMIT/run.log

if grep -q 'GIVE UP SUITE' ~/volume/log/$COMMIT/run.log
then
	echo SET ERROR STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO_REF error
	exit 1
fi

if grep -q 'COMPLETE SUITE' ~/volume/log/$COMMIT/run.log
then
	echo SET SUCCESS STATUS
	cd ~/make-busy/ci && php update-status.php $TOKEN $REPO_REF success
	exit 0
fi

echo SET FAILURE STATUS > /dev/null
cd ~/make-busy/ci && php update-status.php $TOKEN $REPO_REF failure
exit 2
