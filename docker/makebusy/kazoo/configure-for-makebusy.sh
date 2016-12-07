#!/bin/sh
NETWORK=${NETWORK:-"kazoo"}
echo Waiting for kazoo.$NETWORK to start
watch -g "docker logs kazoo.$NETWORK | grep 'auto-started kapps'" > /dev/null
sup ecallmgr_maintenance add_fs_node freeswitch@freeswitch.$NETWORK
sup kapps_config set token_buckets tokens_fill_rate 100
sup kapps_config set token_buckets crossbar {}
sup kapps_config set callflow.park default_ringback_timeout 5000
sup kapps_config set conferences route_win_timeout 3000
sup crossbar_init start_mod cb_system_configs
sup kazoo_maintenance console_level debug
sup kz_datamgr flush_cache_docs
