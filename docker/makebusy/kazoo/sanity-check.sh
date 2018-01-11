#!/bin/sh
NETWORK=${NETWORK:-"kazoo"}

# wait 10s so kz_nodes are published
sleep 10

echo Kazoo status
sup crossbar kz_nodes status

echo Kazoo ACL
sup callmgr ecallmgr_maintenance acl_summary

#echo Kamailio Dispatcher
#docker exec kamailio.$NETWORK kamcmd dispatcher.list | grep URI | sed "s/^[ \t]*//"
