#!/bin/bash
TIMEOUT=${1:-"60"}
NETWORK=${NETWORK:-"kazoo"}
echo Waiting for kazoo.$NETWORK to start
timeout $TIMEOUT watch -g "docker logs kazoo.$NETWORK | grep 'auto-started kapps'" > /dev/null
if [ $? -ne 0 ]
then
	echo Failed to wait for Kazoo to start, network:$NETWORK status:$?
	exit $?
fi
sup ecallmgr_maintenance add_fs_node freeswitch@freeswitch.$NETWORK
sup kapps_config set token_buckets tokens_fill_rate 100
sup kapps_config set token_buckets crossbar {}
sup kapps_config set callflow.park default_ringback_timeout 5000
sup kapps_config set conferences route_win_timeout 3000
sup crossbar_init start_mod cb_system_configs
sup kazoo_maintenance console_level debug
sup kz_datamgr flush_cache_docs
exit 0