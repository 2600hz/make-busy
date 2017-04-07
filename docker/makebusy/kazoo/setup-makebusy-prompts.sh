#!/bin/sh
NETWORK=${NETWORK:-"kazoo"}
mkdir mk-bs
docker cp makebusy.$NETWORK:/home/user/make-busy/prompts/make-busy-media.tar.gz mk-bs/
cd mk-bs
tar zxvf make-busy-media.tar.gz
rm -f make-busy-media.tar.gz
docker cp ../mk-bs kazoo.$NETWORK:/home/user
sup kazoo_media_maintenance import_prompts /home/user/mk-bs/ mk-bs
cd ../
# rm -rf mk-bs
