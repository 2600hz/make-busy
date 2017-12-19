FROM alpine as build

RUN apk update \
    && apk add alpine-sdk swig php7-dev=7.1.12-r0 \
    && ln `which swig` `which swig`3.0 \
    && adduser -D user \
    && addgroup user abuild \
    && echo "user ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/user \
    && chmod 0440 /etc/sudoers.d/user

WORKDIR /home/user
USER user
RUN abuild-keygen -a -i -n \
    && sudo mkdir -p /var/cache/distfiles \
    && sudo chmod a+w /var/cache/distfiles \
    && git clone git://git.alpinelinux.org/aports
WORKDIR /home/user/aports/main/freeswitch
RUN abuild fetch \
    && abuild unpack \
    && abuild prepare \
    && abuild -r deps \
    && echo "event_handlers/mod_event_socket" > modules.conf \
    && abuild build \
    && sed 's/php5/php7/g' -i src/freeswitch-1.6.19/libs/esl/php/Makefile.in \
    && sed 's/php5/php7/g' -i src/freeswitch-1.6.19/libs/esl/php/Makefile.am \
    && cd src/freeswitch-1.6.19/libs/esl && make reswig && make phpmod
	
FROM php:7.1-alpine
MAINTAINER 2600hz <engineering@2600hz.com>
ARG REPO=https://github.com/2600hz/make-busy.git
RUN  apk update && apk add sudo libstdc++ git unzip libcap bash jq \
     && adduser -D -s /bin/bash user \
     && echo "user ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/user \
     && chmod 0440 /etc/sudoers.d/user
WORKDIR /home/user
USER user
RUN  git clone $REPO ./make-busy && cd make-busy && ./composer install && ./composer clear-cache
COPY --from=build /home/user/aports/main/freeswitch/src/freeswitch-1.6.19/libs/esl/php/*.php /home/user/make-busy/src/MakeBusy/FreeSWITCH/ESL/
COPY --from=build /home/user/aports/main/freeswitch/src/freeswitch-1.6.19/libs/esl/php/ESL.so /home/user/make-busy/src/MakeBusy/FreeSWITCH/ESL/
RUN sudo mv /home/user/make-busy/src/MakeBusy/FreeSWITCH/ESL/ESL.so `php-config --extension-dir` \
    && sudo chmod o+w /home/user/make-busy/src/MakeBusy/FreeSWITCH/ESL/*.php \
    && sudo setcap CAP_NET_BIND_SERVICE+ep /usr/local/bin/php
WORKDIR /home/user/make-busy
ADD config.json /home/user/make-busy
ENTRYPOINT ["php", "-S", "0.0.0.0:80"]
