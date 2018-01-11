#!/bin/bash
TIMEOUT=${1:-"120"}
NETWORK=${NETWORK:-"kazoo"}

# sanity check
command -v sup >/dev/null 2>&1 || { echo "sup is required, but missing"; exit 1; }

./wait-for-node.sh kazoo $TIMEOUT
./wait-for-node.sh callmgr $TIMEOUT
./wait-for-node.sh media $TIMEOUT
./wait-for-node.sh crossbar $TIMEOUT

## this should be done with configs for config.ini
#echo -n "enable console debug: "
#sup kazoo kazoo_maintenance console_level debug
#sup crossbar kazoo_maintenance console_level debug
#sup callmgr kazoo_maintenance console_level debug
#sup media kazoo_maintenance console_level debug

echo "adding media files"
sup media kazoo_media_maintenance import_prompts /media-files/en-mb/ en-mb > /dev/null 2>&1
sup media kazoo_media_maintenance import_prompts /media-files/en-us/ en-us > /dev/null 2>&1 

echo "adding freeswitch to kazoo: "
sup callmgr ecallmgr_maintenance add_fs_node freeswitch@freeswitch.$NETWORK

echo wait for freeswitch to complete connect
if ! ./t.sh callmgr.$NETWORK 60 "fs sync complete"; then
	echo Failed to wait for FreeSwitch to connect, network:$NETWORK
	exit 1
fi

sup callmgr ecallmgr_maintenance allow_sbc kamailio.$NETWORK
sup callmgr ecallmgr_maintenance allow_carrier makebusy-fs-carrier.$NETWORK

#echo -n "start crossbar cb_system_configs: "
#sup crossbar crossbar_maintenance start_module cb_system_configs
#sup crossbar crossbar_maintenance start_module cb_quickcall

#echo -n "flush cache docs: "
#sup kz_datamgr flush_cache_docs

exit 0
