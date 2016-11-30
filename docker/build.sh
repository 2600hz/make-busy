#!/bin/bash

function build_image {
	pushd $1 > /dev/null
	./build.sh
	popd > /dev/null
}

build_image makebusy-fs
build_image makebusy
