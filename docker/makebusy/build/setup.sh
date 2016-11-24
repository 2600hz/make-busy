#!/bin/sh
git clone $REPO ./make-busy
cd make-busy
git reset --hard $COMMIT
