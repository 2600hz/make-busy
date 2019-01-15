
## start

```
root@fl03:~# cd /tmp && mkdir test1 && cd test1
root@fl03:~# git clone https://github.com/2600hz/make-busy.git
root@fl03:~# mkdir mytests && cd mytests
root@fl03:~# git clone https://github.com/2600hz/make-busy-callflow.git Callflow
root@fl03:~# git clone https://github.com/2600hz/make-busy-conference.git Conference
root@fl03:~# git clone https://github.com/2600hz/make-busy-crossbar.git Crossbar
root@fl03:~# cd ..
===> we need to pass full path for volume ex: /tmp/test1/make-busy
root@fl03:~# docker run -td --name mkbusy \
                  -v /tmp/test1/make-busy:/root/make-busy \
                  -v /tmp/test1/mytests:/root/tests \
                  --privileged docker:dind --experimental --storage-driver=overlay
root@fl03:~# docker exec -ti mkbusy sh
   # docker swarm init
   # apk --update add git jq bash coreutils
   # export PATH=$PATH:~/make-busy/docker/bin
   
   # export COMMIT=4277e28f4d
   # if exported, the commit can be ommited in next commands
   
   # kazoo up 4277e28f4d
   # wait-for crossbar 4m "finished system schemas update"
   # sup crossbar crossbar_maintenance create_account admin admin admin admin
   # mkbusy up 4277e28f4d
   # wait-for makebusy-fs-auth 120 "FreeSWITCH Started"
   # wait-for makebusy-fs-carrier 120 "FreeSWITCH Started"
   # kazoo configure
   # kazoo check
   
   # mkbusy run-all
   
   running a single test, test shold be ClassName, separate namespace with : 
   # LOG_CONSOLE=1 run-test SetupOwner
   # LOG_CONSOLE=1 run-test Device:TransferBlind
   
   running a suite of tests
   # LOG_CONSOLE=1 run-suite voicemail
   # LOG_CONSOLE=1 run-suite voicemail,incoming

```
