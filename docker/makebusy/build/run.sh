#!/bin/bash
build/make-config.sh $NETWORK
/usr/local/bin/php -S 0.0.0.0:80
