# Docker how-to

## Docker setup

We suppose that there is a designated docker network to run MakeBusy images (named kazoo by default).
You need to make sure that Kazoo's Kamailio, FreeSwitch and Kazoo's Crossbar application are
accessible from this docker network segment, and to specify correct settings in [config.json](makebusy/etc/config.json.dist) file,
namely kamailio.kazoo, kazoo.kazoo, and kazoo admin credentials.

All required docker images are build under *2600hz/* namepsace, namely:

1. 2600hz/makebusy-fs
2. 2600hz/makebusy

All images are intended to be self-buildable.

## Kazoo setup

In order to test some features (e.g. Voicemail or Conference) you need to install special voice prompts as mk-bs
language, and tune some Kazoo variables.

There are two sample shell scripts to assist you to prepare Kazoo:

* [makebusy/kazoo/configure-for-makebusy.sh](makebusy/kazoo/configure-for-makebusy.sh)
* [makebusy/kazoo/setup-makebusy-prompts.sh](makebusy/kazoo/setup-makebusy-prompts.sh).

Scripts assume that Kazoo's *sup* command is in the shell search PATH, and local files are accessible by Kazoo (to load prompts).
But you can do the Kazoo initial setup manually as well by look at the scripts and run the manually.

## Build and run MakeBusy in Docker

```sh
cd docker
./build.sh # optional -- you can use publically available docker images
./run.sh
```
## Using MakeBusy as Docker container

Suppose you have your tests on your host file system like this:
/home/kazoo/make-busy-tests/Callflow/Device/Milliwatt.php

In order to ease test development you can mount the your tests folder as docker volume:
```sh
cd docker/makebusy
TESTS_PATH="/home/kazoo/make-busy-tests" ./run.sh
```

Here /home/kazoo/make-busy-tests will be mounted in tests/KazooTests/Applications folder in MakeBusy docker container.

There is a [shell wrapper](../bin/run-tests.sh) to ease invocation of MakeBusy:
```sh
LOG_CONSOLE=1 ./run-test.sh -v --debug Callflow/Device/Milliwatt.php
```

It passes environment variables and shell options to [run-test](../run-test) MakeBusy test-runner wrapper. It seems feasible to
make the run-tests.sh wrapper accessible from your PATH variable. 

Please see [example tests project](https://github.com/2600hz/make-busy-skel) for see how to write test with MakeBusy.

## Running Tests without wrapper

```sh
docker exec -ti makebusy.kazoo ../run-tests tests/KazooTests/Applications/{path_to_test.php}
```

with ENV options:

```sh
docker exec -ti makebusy.kazoo /bin/bash -c "LOG_CONSOLE=1 ../run-tests tests/KazooTests/Applications/{path_to_test.php}"
```
