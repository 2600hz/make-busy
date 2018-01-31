# Getting Started - The Long Guide

The aim here is to take a relatively vanilla system and get MakeBusy and Kazoo running together.

## Clone the repo

```bash
git clone https://github.com/2600hz/make-busy
cd make-busy
```

## Composer Update

Upgrade composer and then update vendor libraries

```bash
./composer self-update
./composer update
```

Create an OAuth token with your github user (with no scopes added) to ensure speedy fetching.

## Clone the test suites

I like to add them as git submodules personally, though nothing requires it

```bash
git submodule add --name Callflow git@github.com:2600hz/make-busy-callflow.git tests/Callflow
git submodule add --name Conference git@github.com:2600hz/make-busy-conference.git tests/Conference
git submodule add --name Crossbar git@github.com:2600hz/make-busy-crossbar.git tests/Crossbar
```

## Install Docker

Please follow the [installation guide](https://docs.docker.com/install/) for your setup.

It is an option to add your user to the `docker` group so avoid needing sudo/root.

## Run MakeBusy Docker image

```bash
docker run -td --name mkbusy \
  -v /path/to/make-busy:/root/make-busy \
  -v /path/to/make-busy/tests:/root/tests \
  --privileged docker:dind --experimental --storage-driver=overlay2
docker run -td --name mkbusy -v /home/james/local/git/2600hz/make-busy/:/root/make-busy -v /home/james/local/git/2600hz/make-busy/tests/:/root/tests --privileged docker:dind --experimental --storage-driver=overlay2
Unable to find image 'docker:dind' locally
dind: Pulling from library/docker
Digest: sha256:9ac740379e36980be82a4a3f3291a58d2413bb554317f1e74fb9e7f808907616
Status: Downloaded newer image for docker:dind
e291a562991a28c3ef3417f6cfca884865781d35adf1f47ed326ae4b51fffa8f
```

`docker:dind` is a Docker-in-Docker image.

You'll want to make sure you're running a linux kernel >= 4.0

## Run MakeBusy Docker image

### Start the swarm
```bash
docker exec -ti mkbusy sh
docker> docker swarm init
Swarm initialized: current node (ggvvewfwaj1kx0ych3zjnqjr4) is now a manager.

To add a worker to this swarm, run the following command:

    docker swarm join --token SWMTKN-1-4pjrl58p0fl2kipo7u95oes9h3x3kg8kke7us5134hm8zn47cs-27g3mo5it7ke24n7zspuqtfzm 172.17.0.2:2377

To add a manager to this swarm, run 'docker swarm join-token manager' and follow the instructions.
```

### Update packages

```bash
docker> apk --update add git jq bash coreutils
fetch http://dl-cdn.alpinelinux.org/alpine/v3.7/main/x86_64/APKINDEX.tar.gz
fetch http://dl-cdn.alpinelinux.org/alpine/v3.7/community/x86_64/APKINDEX.tar.gz
(1/16) Installing pkgconf (1.3.10-r0) OK
(2/16) Installing ncurses-terminfo-base (6.0_p20171125-r0) OK
(3/16) Installing ncurses-terminfo (6.0_p20171125-r0) OK
(4/16) Installing ncurses-libs (6.0_p20171125-r0) OK
(5/16) Installing readline (7.0.003-r0) OK
(6/16) Installing bash (4.4.12-r2) OK
(7/16) Installing libattr (2.4.47-r6) OK
(8/16) Installing libacl (2.2.52-r3) OK
(9/16) Installing coreutils (8.28-r0) OK
(10/16) Installing libssh2 (1.8.0-r2) OK
(11/16) Installing libcurl (7.57.0-r0) OK
(12/16) Installing expat (2.2.5-r0) OK
(13/16) Installing pcre2 (10.30-r0) OK
(14/16) Installing git (2.15.0-r1) OK
(15/16) Installing oniguruma (6.6.1-r0) OK
(16/16) Installing jq (1.5-r4) OK
Executing busybox-1.27.2-r7.trigger
OK: 46 MiB in 51 packages
```

### Add PATH location

```bash
docker> export PATH=$PATH:~/make-busy/bin
```

### Export Kazoo git SHA

This is the commit SHA you want to run the test suite(s) against.

```bash
docker> export COMMIT=abcdef
```

### Start Kazoo

This will build Kazoo - get a coffee, croissant, do some pushups, whatever.

```bash
docker> kazoo up
Sending build context to Docker daemon  6.144kB
Step 1/19 : FROM 2600hz/kazoo-build as build
latest: Pulling from 2600hz/kazoo-build
...
Step 19/19 : LABEL "kazoo.commit"='abcdef'
 ---> Running in c67380cd32c1
Removing intermediate container c67380cd32c1
 ---> d72cc468c9aa
Successfully built fe4136347b56
Successfully tagged 2600hz/kazoo:abcdef
Creating network kz_abcdef_kazoo
Creating service kz_abcdef_kazoo
Creating service kz_abcdef_crossbar
Creating service kz_abcdef_callmgr
Creating service kz_abcdef_media
Creating service kz_abcdef_kamailio
Creating service kz_abcdef_couchdb
Creating service kz_abcdef_rabbitmq
Creating service kz_abcdef_freeswitch
```

### Let Kazoo get setup

```bash
docker> wait-for crossbar 4m "finished system schemas update"
waiting for 'finished system schemas update' in node 'crossbar' => 017073060c18
found 'finished system schemas update' in crossbar
docker> sup crossbar crossbar_maintenance create_account admin admin admin admin
```
