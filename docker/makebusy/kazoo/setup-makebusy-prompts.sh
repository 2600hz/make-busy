#!/bin/sh
NETWORK=${NETWORK:-"kazoo"}
mkdir en-mb
docker cp makebusy.$NETWORK:/home/user/make-busy/prompts/make-busy-media.tar.gz en-mb/
cd en-mb
tar zxvf make-busy-media.tar.gz
rm -f make-busy-media.tar.gz
docker cp ../en-mb kazoo.$NETWORK:/home/user
sup kazoo_media_maintenance import_prompts /home/user/en-mb/ en-mb
cd ../
# rm -rf en-mb
