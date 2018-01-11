#!/bin/sh
NODE=${1:-"kazoo"}
TIMEOUT=${2:-"120"}
SEARCH_TERM=${3:-"nada"}

echo Waiting for "$SEARCH_TERM" in $NODE for $TIMEOUT
(timeout --foreground $TIMEOUT docker logs -f $NODE &) | grep -q "$SEARCH_TERM" && echo "found '$SEARCH_TERM' in $NODE" && exit 0
echo "Timeout of $TIMEOUT reached. Unable to find '$SEARCH_TERM' in $NODE"
exit 1

