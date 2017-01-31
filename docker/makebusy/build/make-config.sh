#!/bin/bash
NETWORK=$1
if [ -z $NETWORK ]
then
	echo Please specify the network segment to run tests in
	exit
fi

jq -M '.media.welcome_prompt_path="/home/user/make-busy/prompts/prompts/welcome.wav"' etc/config.json.dist > config.json
/bin/sed -i "s/\.kazoo/\.$NETWORK/g" config.json
