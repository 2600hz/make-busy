#!/bin/bash
NETWORK=${NETWORK:-"kazoo"}
REOPTS=""
for ARG in "$@"
do
	if [ "${ARG: -4}" == ".php" ]
	then
		FILE=$ARG
	else
		REOPTS="$REOPTS $ARG"
	fi
done
if [ -z $FILE ]
then
	echo Please specify the test file relatively to your tests folder mounted in MakeBusy container
	exit
fi
REEXPORT=""
for var in LOG_CONSOLE CLEAN REGISTER_PROFILE RESTART_PROFILE DUMP_EVENTS DUMP_ENTITIES
do
	VALUE=$(eval echo \$$var)
	if [ ! -z $VALUE ]
	then
		REEXPORT="$REEXPORT $var=$VALUE"
	fi
done
docker exec -i makebusy.$NETWORK /bin/bash -c "$REEXPORT ./run-test $REOPTS tests/KazooTests/Applications/$FILE"
