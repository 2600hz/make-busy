#!/bin/sh
if [ -d /home/user/volume ]
then
	rm -rf /home/user/volume/*
	cp -a /home/user/tests/* /home/user/volume/
fi
