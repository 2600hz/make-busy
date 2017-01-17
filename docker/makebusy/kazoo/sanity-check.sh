#!/bin/sh
NETWORK=${NETWORK:-"kazoo"}
echo Kazoo status
sup kz_nodes status

echo Kazoo ACL
sup ecallmgr_maintenance acl_summary

echo Kamailio Dispatcher
docker exec kamailio.$NETWORK kamcmd dispatcher.list
