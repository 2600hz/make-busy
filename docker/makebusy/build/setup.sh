#!/bin/sh
cd /var/www/html
git clone $REPO ./make-busy
cd make-busy
git reset --hard $COMMIT
./composer install
