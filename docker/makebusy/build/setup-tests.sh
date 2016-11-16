#!/bin/sh
cd /var/www/html/make-busy
mkdir -p tests/KazooTests/Applications
git clone git@github.com:2600hz/make-busy-callflow.git tests/KazooTests/Applications/Callflow
