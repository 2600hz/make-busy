#!/bin/sh
FSCFG=/usr/local/freeswitch/conf

IP=`hostname -i`
echo type:$TYPE ip:$IP

sed -i "s|\"sip-ip\" value=\".*\"|\"sip-ip\" value=\"$IP\"|" $FSCFG/sip_profiles/profile.xml
sed -i "s|\"ext-sip-ip\" value=\".*\"|\"ext-sip-ip\" value=\"$IP\"|" $FSCFG/sip_profiles/profile.xml
sed -i "s|\"rtp-ip\" value=\".*\"|\"rtp-ip\" value=\"$IP\"|" $FSCFG/sip_profiles/profile.xml
sed -i "s|\"ext-rtp-ip\" value=\".*\"|\"ext-rtp-ip\" value=\"$IP\"|" $FSCFG/sip_profiles/profile.xml

sed -i "s|cmd=\"exec\" data=\".*|cmd=\"exec\" data=\"wget -qO - $MAKEBUSY_URL/gateways.php?type=$TYPE\"/>|" $FSCFG/sip_profiles/profile.xml
