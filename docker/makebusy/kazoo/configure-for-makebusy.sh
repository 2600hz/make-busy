#!/bin/bash
TIMEOUT=${1:-"120"}
NETWORK=${NETWORK:-"kazoo"}
echo Waiting for kazoo.$NETWORK to start
timeout --foreground $TIMEOUT watch -g "docker logs kazoo.$NETWORK | grep 'auto-started kapps'" > /dev/null
if [ $? -ne 0 ]
then
	echo Failed to wait for Kazoo to start, network:$NETWORK
	exit 1
fi

echo -n "enable console debug: "
sup kazoo_maintenance console_level debug

echo -n "adding freeswitch to kazoo: "
sup ecallmgr_maintenance add_fs_node freeswitch@freeswitch.$NETWORK

echo wait for freeswitch to complete connect
timeout --foreground $TIMEOUT watch -g "docker logs kazoo.$NETWORK | grep 'max_channel_cleanup_timeout_ms'" > /dev/null
if [ $? -ne 0 ]
then
	echo Failed to wait for FreeSwitch to connect, network:$NETWORK
	exit 1
fi

IP=$(docker inspect --format "{{ (index .NetworkSettings.Networks \"$NETWORK\").IPAddress }}" kamailio.$NETWORK)
echo -n "add kamailio.$NETWORK to kazoo.$NETWORK ACL with ip $IP: "
sup ecallmgr_maintenance allow_sbc kamailio.$NETWORK $IP

echo -n "set config token_buckets.token_fill_rate: "
sup kapps_config set token_buckets tokens_fill_rate 100

echo -n "set config token_buckets.crossbar: "
sup kapps_config set token_buckets crossbar {}

echo -n "set config callflow.park.default_ringback_timeout: "
sup kapps_config set callflow.park default_ringback_timeout 5000

echo -n "set config conferences.route_win_timeout: "
sup kapps_config set conferences route_win_timeout 3000

echo -n "set config stepswitch.block_anonymous_caller_id: "
sup kapps_config set stepswitch block_anonymous_caller_id false

echo -n "start crossbar cb_system_configs: "
sup crossbar_init start_mod cb_system_configs

echo -n "flush cache docs: "
sup kz_datamgr flush_cache_docs
exit 0
