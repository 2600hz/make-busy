#!/bin/sh
NETWORK=${NETWORK:-"kazoo"}
echo Waiting for kazoo.$NETWORK to start '(you may check docker logs if impatient)'
watch -g "docker logs kazoo.$NETWORK | grep 'auto-started kapps'" > /dev/null

echo Init the system
sup crossbar_maintenance create_account admin admin admin admin

echo Allow Kamailio
sup ecallmgr_maintenance allow_carrier kamailio $(gethostip -d kamailio.$NETWORK)

git clone --depth 1 --no-single-branch https://github.com/2600hz/kazoo-sounds
docker cp kazoo-sounds/kazoo-core/en/us kazoo.$NETWORK:/home/user
sup kazoo_media_maintenance import_prompts /home/user/us en-us
docker exec --user root kazoo.$NETWORK rm -rf us

mkdir mk-bs
docker cp makebusy.$NETWORK:/home/user/make-busy/prompts/make-busy-media.tar.gz mk-bs/
cd mk-bs
tar zxvf make-busy-media.tar.gz
rm -f make-busy-media.tar.gz
docker cp ../mk-bs kazoo.$NETWORK:/home/user
sup kazoo_media_maintenance import_prompts /home/user/mk-bs/ mk-bs
cd ../
rm -rf mk-bs

# wait kazoo to digest files
sleep 10
# save it for future use (e.g. clear things)
docker commit couchdb.$NETWORK kazoo/couchdb-mkbs
cd ../
