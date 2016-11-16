# Docker how-to

## Setup

We suppose that there is a designated docker network to run MakeBusy images (kazoo by default).
You need to make sure that Kazoo's Kamailio, FreeSwitch and Kazoo's Crossbar application are
accessible from this docker network segment.

## Kazoo Setup

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
## Run tests
