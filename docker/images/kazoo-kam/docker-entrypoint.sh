#!/usr/bin/env bash

# first arg is `-something` 
if [ "${1#-}" != "$1" ]; then
    set -- kamailio "$@"
fi

service=${ENABLE_SERVICE_NAME:-false}
advertise=${ENABLE_ADVERTISE:-false}
public=${ENABLE_PUBLIC_SERVICE:-false}

AMQP_ZONE=${AMQP_ZONE:-local}
AMQP_USER=${AMQP_USER:-guest}
AMQP_PASS=${AMQP_PASSWORD:-guest}
AMQP_SERVER=${AMQP_SERVER:-rabbitmq}
AMQP_VIRTUAL_SERVER=${AMQP_VIRTUAL_SERVER:-}

WSS_DOMAIN=${WSS_DOMAIN:-`hostname -d`}

MY_ADVERTISED_IP_ADDRESS=${MY_ADVERTISED_IP_ADDRESS:-`curl -s checkip.amazonaws.com`}
MY_HOSTNAME=`hostname -f`

if [ "$service" = "true" ]; then
   MY_IP_ADDRESS=`ip route | grep -v default | grep eth2 | cut -d' ' -f9`
else
   MY_IP_ADDRESS=`ip route | grep -v default | grep eth0 | cut -d' ' -f9`
fi

if [ "$service" = "true" ]; then
   MY_OUTSIDE_IP_ADDRESS=`ip route | grep -v default | grep eth0 | cut -d' ' -f9`
else
   MY_OUTSIDE_IP_ADDRESS=`ip route | grep -v default | grep eth1 | cut -d' ' -f9`
fi

if [ "$advertise" = "true" ]; then
   DO_ADVERTISE="#!define ADVERTISED_LISTENER"
else
   DO_ADVERTISE=""
fi

if [ "$public" = "true" ]; then
   DO_PUBLIC="#!define PUBLIC_LISTENER"
else
   DO_PUBLIC=""
fi

cat > /etc/kamailio/dynamic.cfg <<EOF

## DYNAMIC CONFIGURATION ##

#!trydef PUSHER_ROLE
#!trydef PRESENCE_NOTIFY_SYNC_ROLE
#!define KZ_DISPATCHER_PROBE_MODE 3

#!substdef "!MY_HOSTNAME!${MY_HOSTNAME}!g"
#!substdef "!MY_IP_ADDRESS!${MY_IP_ADDRESS}!g"
#!substdef "!MY_AMQP_ZONE!${AMQP_ZONE}!g"
#!substdef "!MY_WEBSOCKET_DOMAIN!${WSS_DOMAIN}!g"
#!substdef "!MY_AMQP_URL!amqp://${AMQP_USER}:${AMQP_PASS}@${AMQP_SERVER}:5672${AMQP_VIRTUAL_SERVER}!g"

${DO_ADVERTISE}
${DO_PUBLIC}


#!ifdef PUBLIC_LISTENER
#!substdef "!MY_OUTSIDE_IP_ADDRESS!${MY_OUTSIDE_IP_ADDRESS}!g"
#!endif


#!ifdef ADVERTISED_LISTENER
#!substdef "!MY_OUTSIDE_IP_ADDRESS!${MY_OUTSIDE_IP_ADDRESS}!g"
#!substdef "!MY_ADVERTISED_IP_ADDRESS!${MY_ADVERTISED_IP_ADDRESS}!g"
#!endif



#!define DYNAMIC_SETTINGS

#!substdef "!KAZOO_DATA_DIR!/etc/kamailio/db!g"

EOF

>&2

cat /etc/kamailio/dynamic.cfg
echo "args : $@"
exec "$@"

