#!/bin/sh
git clone $REPO ./make-busy
cd make-busy
./composer install && ./composer clear-cache
