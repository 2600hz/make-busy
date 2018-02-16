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
docker> export PATH=$PATH:~/make-busy/docker/bin
```

### Export Kazoo git SHA

This is the commit SHA you want to run the test suite(s) against. Please limit it to the first 10 characters of the SHA.

```bash
docker> export COMMIT=abcdef0123
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
View updated for account%2F2e%2Fbf%2Fa92013a7b0d447782a18098157e4!
created new account '2ebfa92013a7b0d447782a18098157e4' in db 'account%2F2e%2Fbf%2Fa92013a7b0d447782a18098157e4'
created new account admin user 'ab83c33b654552cf98282d9d77ca86e1'
promoting account 2ebfa92013a7b0d447782a18098157e4 to reseller status, updating sub accounts
updated master account id in system_config.accounts
```

### Start up make-busy system

This includes vanilla FreeSWITCH instances

```bash
docker> mkbusy up 98a82f83ba
Creating service mb_98a82f83ba_makebusy-fs-carrier
Creating service mb_98a82f83ba_makebusy
Creating service mb_98a82f83ba_makebusy-fs-auth
Creating service mb_98a82f83ba_makebusy-fs-pbx
docker> wait-for makebusy-fs-auth 120 "FreeSWITCH Started"
waiting for 'FreeSWITCH Started' in node 'makebusy-fs-auth' => 7a7dacc5f469
found 'FreeSWITCH Started' in makebusy-fs-auth
docker> wait-for makebusy-fs-carrier 120 "FreeSWITCH Started"
waiting for 'FreeSWITCH Started' in node 'makebusy-fs-carrier' => d91c14478a82
found 'FreeSWITCH Started' in makebusy-fs-carrier
```

This is where the dind (Docker in Docker) comes in. You are connected to the "outer" docker instance. `mkbusy up` takes a snapshot and builds many docker containers within the "outer" docker instance. This means changes to a file under `vendor` (for instance the PHP SDK libs) and other directories, won't be reflected at runtime when running tests. You'll need to connect to the inner Docker instance (`mb_$(COMMIT)_makebusy`) and edit the files there.

### Configure Kazoo for make-busy testing

```bash
docker> kazoo configure
waiting for 'auto-started kapps' in node 'kz_98a82f83ba_kazoo' => 989f7816b977
found 'auto-started kapps' in kz_98a82f83ba_kazoo
waiting for 'auto-started kapps' in node 'kz_98a82f83ba_callmgr' => 72606d0506dd
found 'auto-started kapps' in kz_98a82f83ba_callmgr
waiting for 'auto-started kapps' in node 'kz_98a82f83ba_media' => d889a1866018
found 'auto-started kapps' in kz_98a82f83ba_media
waiting for 'auto-started kapps' in node 'kz_98a82f83ba_crossbar' => bfda06c4c5fd
found 'auto-started kapps' in kz_98a82f83ba_crossbar
setting freeswitch ip to 10.0.1.11 freeswitch-1 freeswitch-1.98a82f83ba
adding freeswitch@freeswitch-1.kz_98a82f83ba to ecallmgr system config
ok
waiting for 'fs sync complete' in node 'kz_98a82f83ba_callmgr' => 72606d0506dd
found 'fs sync complete' in kz_98a82f83ba_callmgr
setting kamailio ip to 10.0.1.8 kamailio-1 kamailio-1.98a82f83ba
updating authoritative ACLs kamailio-1.98a82f83ba(10.0.1.8/32) to allow traffic
issued reload ACLs to freeswitch@freeswitch-1.kz_98a82f83ba
setting makebusy-fs-carrier ip to 10.0.1.16 makebusy-fs-carrier makebusy-fs-carrier.98a82f83ba
updating trusted ACLs makebusy-fs-carrier.98a82f83ba(10.0.1.16/32) to allow traffic
issued reload ACLs to freeswitch@freeswitch-1.kz_98a82f83ba
docker> sup callmgr ecallmgr_maintenance reload_acls
issued reload ACLs to freeswitch@freeswitch-1.kz_98a82f83ba
docker> kazoo check
Node          : media@media-1.kz_98a82f83ba
md5           : N5DqygdpWlscOfdQjTWkfw
Version       : 4.0.0 - 20
Memory Usage  : 71.26MB
Processes     : 1610
Ports         : 14
Zone          : local
Broker        : amqp://rabbitmq:5672
Globals       : total (0)
Node Info     : kz_amqp_pool: 250/0/0 (ready)
WhApps        : media_mgr(12m4s)

Node          : kamailio@kamailio-1.kz_98a82f83ba
Version       : 5.1.0-dev8
Memory Usage  : 16.54MB
Zone          : local
Broker        : amqp://rabbitmq:5672
WhApps        : kamailio(12m32s)
Roles         : Dispatcher Presence Proxy Registrar
Dispatcher 1  : sip:10.0.1.11:11000 (AP)
Subscribers   :
Subscriptions :
Presentities  : presence (0)  dialog (0)  message-summary (0)
Registrations : 0

Node          : ecallmgr@callmgr-1.kz_98a82f83ba
md5           : 9y7E8GQapsLXFPLz7La-Xg
Version       : 4.0.0 - 20
Memory Usage  : 72.12MB
Processes     : 1672
Ports         : 26
Zone          : local
Broker        : amqp://rabbitmq:5672
Globals       : total (0)
Node Info     : kz_amqp_pool: 250/0/0 (ready)
WhApps        : ecallmgr(12m4s)
Channels      : 0
Conferences   : 0
Registrations : 0
Media Servers : freeswitch@freeswitch-1.kz_98a82f83ba (1m35s)

Node          : apps@kazoo-1.kz_98a82f83ba
md5           : 2hc4eVFgzML_rGyQ9E3KEA
Version       : 4.0.0 - 20
Memory Usage  : 74.75MB
Processes     : 1865
Ports         : 15
Zone          : local
Broker        : amqp://rabbitmq:5672
Globals       : total (0)
Node Info     : kz_amqp_pool: 250/0/0 (ready)
WhApps        : blackhole(12m3s)         callflow(12m3s)          conference(12m3s)        pusher(12m3s)
                registrar(12m3s)         stepswitch(12m3s)        sysconf(12m4s)           trunkstore(12m3s)

                Node          : api@crossbar-1.kz_98a82f83ba
                md5           : fG3YrnyPUsjws5r-dXJqEw
                Version       : 4.0.0 - 20
                Memory Usage  : 80.36MB
                Processes     : 1759
                Ports         : 15
                Zone          : local
                Broker        : amqp://rabbitmq:5672
                Globals       : total (0)
                Node Info     : kz_amqp_pool: 250/0/0 (ready)
                WhApps        : crossbar(12m2s)

                +--------------------------------+--------------------+---------------+-------+------------------+----------------------------------+
                | Name                           | CIDR               | List          | Type  | Authorizing Type | ID                               |
                +================================+====================+===============+=======+==================+==================================+
                | kamailio-1.98a82f83ba          | 10.0.1.8/32        | authoritative | allow | system_config    |                                  |
                | makebusy-fs-carrier.98a82f83ba | 10.0.1.16/32       | trusted       | allow | system_config    |                                  |
                +--------------------------------+--------------------+---------------+-------+------------------+----------------------------------+
```

### Run the test suite(s)

#### Run all the test suites

```bash
docker> mkbusy run $COMMIT
```

#### Run a suite of tests

```bash
docker> HUPALL=1 LOG_CONSOLE=1 run-suite Callflow/Voicemail
```

#### Run a single test

```bash
docker> HUPALL=1 LOG_CONSOLE=1 run-test Callflow/Voicemail/SetupOwner.php
```

### Read the logs

Now that you've run some tests, it is nice to get some logs to find where a particular test went awry.

First, find the container you want logs from:

```bash
docker> docker ps
CONTAINER ID        IMAGE                     COMMAND                  CREATED             STATUS              PORTS                                                 NAMES
d1b2772a84ae        2600hz/mkbusy:latest      "php -S 0.0.0.0:8080"    24 minutes ago      Up 24 minutes                                                             mb_b72d15fc_makebusy.1.1fihj0skxm6fresw1eiw5m93u
ecbfd71196e5        2600hz/mkbusy-fs:latest   "freeswitch -nonat"      24 minutes ago      Up 24 minutes                                                             mb_b72d15fc_makebusy-fs-carrier.1.8u8q0z5vhc3741357su7uz225
9027c1cfe261        2600hz/mkbusy-fs:latest   "freeswitch -nonat"      24 minutes ago      Up 24 minutes                                                             mb_b72d15fc_makebusy-fs-pbx.1.60plb6yhtbu6hgkl5gxf80rkb
d21ebf16ff44        2600hz/mkbusy-fs:latest   "freeswitch -nonat"      24 minutes ago      Up 24 minutes                                                             mb_b72d15fc_makebusy-fs-auth.1.kuuyphll43wq91dkymm75kpo6
a0a741ac29e7        apache/couchdb:latest     "tini -- /docker-ent…"   30 minutes ago      Up 30 minutes       4369/tcp, 5984/tcp, 9100/tcp                          kz_b72d15fc_couchdb.1.8f6mv8lvb8ombztjhuz9vzv43
dc3c77393eb5        2600hz/kamailio:edge      "/docker-entrypoint.…"   30 minutes ago      Up 30 minutes                                                             kz_b72d15fc_kamailio.1.d82gvvew0ro5a5rrouw1mdvqy
806467d66417        2600hz/kazoo-fs:latest    "freeswitch -nonat"      30 minutes ago      Up 30 minutes                                                             kz_b72d15fc_freeswitch.1.g7blbl1bih1lw2bsm3irnhtas
6ff95a96483f        rabbitmq:3-management     "docker-entrypoint.s…"   30 minutes ago      Up 30 minutes       4369/tcp, 5671-5672/tcp, 15671-15672/tcp, 25672/tcp   kz_b72d15fc_rabbitmq.1.7nb8crtvvehx8osa640m430u2
419cee0a2fa9        2600hz/kazoo:b72d15fc     "kazoo foreground"       31 minutes ago      Up 30 minutes                                                             kz_b72d15fc_media.1.qodszboyu2at46fdweoxuvrw0
eb34dce0e11e        2600hz/kazoo:b72d15fc     "kazoo foreground"       31 minutes ago      Up 31 minutes                                                             kz_b72d15fc_callmgr.1.znhr6530baxqoi1vcerlgzfns
4e7e70112a9d        2600hz/kazoo:b72d15fc     "kazoo foreground"       31 minutes ago      Up 31 minutes                                                             kz_b72d15fc_crossbar.1.yc3eohzgv6zurscdwnaw0ox3v
4513ca6c51a5        2600hz/kazoo:b72d15fc     "kazoo foreground"       31 minutes ago      Up 31 minutes                                                             kz_b72d15fc_kazoo.1.uhfq3j0309yx9k6qnhp2218bq
```

Choose the appropriate container ID and then fetch logs. For instance, if we want to see logs for a call `XYZ` in ecallmgr, we'd use `kz_b72d15fc_callmgr.1.znhr6530baxqoi1vcerlgzfns` (container ID `eb34dce0e11e`):

```bash
docker logs eb34dce0e11e | grep XYZ
```

### Building from a new commit

With an existing docker container running:

#### Stop the running setup

```bash
docker> mkbusy down
docker> kazoo down
```
#### Start from the new commit


```bash
docker> export COMMIT={NEW_COMMIT}
docker> kazoo up
docker> mkbusy up
```
