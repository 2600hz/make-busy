#!/bin/sh
FLAGS=${1:-""}
docker build $FLAGS -t 2600hz/makebusy-fs .
