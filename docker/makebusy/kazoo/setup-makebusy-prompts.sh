#!/bin/sh
mkdir mk-bs
docker cp makebusy.kazoo:/var/www/html/make-busy/prompts/make-busy-media.tar.gz mk-bs/
cd mk-bs
tar zxvf make-busy-media.tar.gz
sup kazoo_media_maintenance import_prompts $PWD mk-bs
cd ../
rm -rf mk-bs
