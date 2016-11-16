#!/bin/bash
cd /var/www/html/make-busy
LOG_CONSOLE=1 ./run-test --tap $@
