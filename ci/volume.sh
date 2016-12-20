#!/bin/sh
if [ -d /home/user/volume ]
then
		  	mkdir -p /home/user/volume/log
			chown -R user:docker /home/user/volume/log
fi
