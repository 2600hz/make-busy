
## start

```
root@fl03:~# git clone https://github.com/2600hz/make-busy.git
root@fl03:~# docker run -td --name mkbusy -v make-busy:/root/make-busy -v mytests:/root/tests \
                  --privileged docker:dind --experimental --storage-driver=overlay
root@fl03:~# docker exec -ti mkbusy sh
   # docker swarm init
   # apk --update add git jq bash
   # export PATH=$PATH:~/make-busy/bin
   # export COMMIT=ce385413cd
   # kazoo up
   # wait-for crossbar 4m "finished system schemas update"
   # sup crossbar crossbar_maintenance create_account admin admin admin admin
   # mkbusy up ce385413cd
   # wait-for makebusy-fs-auth 120 "FreeSWITCH Started"
   # wait-for makebusy-fs-carrier 120 "FreeSWITCH Started"
   # kazoo configure
   # sup callmgr ecallmgr_maintenance reload_acls
   # kazoo check
   # mkbusy run
```