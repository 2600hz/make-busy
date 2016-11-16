# Docker how-to

## Docker setup

We suppose that there is a designated docker network to run MakeBusy images (named kazoo by default).
You need to make sure that Kazoo's Kamailio, FreeSwitch and Kazoo's Crossbar application are
accessible from this docker network segment, and to specify correct settings in [config.json](makebusy/etc/config.json.dist) file,
namely kamailio.kazoo, kazoo.kazoo, and kazoo admin credentials.

All required docker images are build under *kazoo/* namepsace, namely:

1. kazoo/base-os
2. kazoo/freeswitch
3. kazoo/makebusy-fs
4. kazoo/makebusy

All images are intended to be self-buildable, and req

## Kazoo setup

In order to test Voicemail and Conference features you need to install special voice prompts as mk-bs
language, and tune some Kazoo variables.

There are two shell scripts to assist you to prepare Kazoo: [setup-kazoo.sh](makebusy/setup-kazoo.sh)
and [setup-kazoo-prompts.sh](makebusy/setup-kazoo-prompts.sh).  Scripts suppose that Kazoo's *sup* command
is in search PATH, and local files are accessible by Kazoo. But you can do the Kazoo initial setup manually
as well.

## Build and run

```sh
cd docker
./build.sh
./run.sh
```
## Develop and run tests

In order to ease test development you can mount the tests folder locally:

```sh
cd docker/makebusy
./run.sh /home/kazoo/make-busy-tests
```
Here /home/kazoo/make-busy-tests will be mounted as Application folder in MakeBusy docker image,
allowing to execute tests.

Please see [example tests structure](https://github.com/jamhed/make-busy-tests).
