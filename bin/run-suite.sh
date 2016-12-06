#!/bin/bash
NETWORK=${NETWORK:-"kazoo"}
SUITE=$1
[ -z $SUITE ] && echo "please specify a folder with tests" && exit 0
[ ! -d $SUITE ] && echo "$SUITE must be a folder with php test files" && exit 0
shift
REEXPORT=""
for var in LOG_CONSOLE CLEAN REGISTER_PROFILE RESTART_PROFILE DUMP_EVENTS DUMP_ENTITIES
do
	VALUE=$(eval echo \$$var)
	if [ ! -z $VALUE ]
	then
		REEXPORT="$REEXPORT $var=$VALUE"
	fi
done
docker exec makebusy.$NETWORK /bin/bash -c "$REEXPORT ./run-suite $REOPTS tests/KazooTests/Applications/$SUITE $*"
