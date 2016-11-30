#!/bin/sh
COMMIT=$(cat commit)
git fetch
git reset --hard $COMMIT
