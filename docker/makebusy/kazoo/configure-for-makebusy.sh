#!/bin/bash
TIMEOUT=${1:-"120"}
NETWORK=${NETWORK:-"kazoo"}

# sanity check
command -v sup >/dev/null 2>&1 || { echo "sup is required, but missing"; exit 1; }

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
timeout --foreground $TIMEOUT watch -g "docker logs kazoo.$NETWORK | grep 'fs sync complete'" > /dev/null
if [ $? -ne 0 ]
then
	echo Failed to wait for FreeSwitch to connect, network:$NETWORK
	exit 1
fi

IP=$(docker inspect --format "{{ (index .NetworkSettings.Networks \"$NETWORK\").IPAddress }}" kamailio.$NETWORK)
echo -n "add kamailio.$NETWORK to kazoo.$NETWORK ACL with ip $IP: "
sup ecallmgr_maintenance allow_sbc kamailio.$NETWORK $IP

IP=$(docker inspect --format "{{ (index .NetworkSettings.Networks \"$NETWORK\").IPAddress }}" makebusy-fs-carrier.$NETWORK)
echo -n "add makebusy-fs-carrier.$NETWORK to kazoo.$NETWORK ACL with ip $IP: "
sup ecallmgr_maintenance allow_carrier makebusy-fs-carrier.$NETWORK $IP

echo -n "start crossbar cb_system_configs: "
sup crossbar_maintenance start_module cb_system_configs

echo -n "flush cache docs: "
sup kz_datamgr flush_cache_docs

exit 0
