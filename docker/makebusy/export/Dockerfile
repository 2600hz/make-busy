FROM php:5-alpine
MAINTAINER Roman Galeev <jamhed@2600hz.com>

# override this (if needed) with run --env option
ENV NETWORK=kazoo

RUN apk add --no-cache libcap bash \
	&& adduser -D -s /bin/bash user \
	&& setcap CAP_NET_BIND_SERVICE+ep /usr/local/bin/php

ADD makebusy.tar /home/

USER user
WORKDIR /home/user/make-busy
ENTRYPOINT [ "../run.sh" ]


