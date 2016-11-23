#!/bin/sh
cd /var/www/html/make-busy
git fetch
git reset --hard $COMMIT
