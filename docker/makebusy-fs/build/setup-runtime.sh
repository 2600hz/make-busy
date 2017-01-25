#!/bin/sh
FSCFG=/usr/local/freeswitch/conf

IP=`hostname -i`
echo type:$TYPE ip:$IP makebusy_url:$MAKEBUSY_URL

xmlstarlet edit -P --inplace -u 'include/profile/settings/param[@name="sip-ip"]/@value' -v $IP conf/sip_profiles/profile.xml
xmlstarlet edit -P --inplace -u 'include/profile/settings/param[@name="ext-sip-ip"]/@value' -v $IP conf/sip_profiles/profile.xml
xmlstarlet edit -P --inplace -u 'include/profile/settings/param[@name="rtp-ip"]/@value' -v $IP conf/sip_profiles/profile.xml
xmlstarlet edit -P --inplace -u 'include/profile/settings/param[@name="ext-sip-ip"]/@value' -v $IP conf/sip_profiles/profile.xml

xmlstarlet edit -P --inplace -u 'include/profile/gateways/X-PRE-PROCESS[@cmd="exec"]/@data' -v "wget -qO - $MAKEBUSY_URL/gateways.php?type=$TYPE" conf/sip_profiles/profile.xml
xmlstarlet edit -P --inplace -u 'configuration/settings/param[@name="loglevel"]/@value' -v debug conf/autoload_configs/console.conf.xml
