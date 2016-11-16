#!/bin/sh
cd base-os
./build.sh
cd ../freeswitch
./build.sh
cd ../makebusy-fs
./build.sh
cd ../makebusy
./build.sh
