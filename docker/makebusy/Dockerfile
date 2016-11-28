FROM php:5-alpine
MAINTAINER Roman Galeev <jamhed@2600hz.com>

USER root
WORKDIR /root

ARG REPO=https://github.com/2600hz/make-busy.git
ARG COMMIT=HEAD

COPY build/setup-os.sh build/setup-os.sh
RUN build/setup-os.sh

WORKDIR /home/user

COPY build/setup.sh build/setup.sh
RUN build/setup.sh

COPY etc/config.json make-busy/config.json

RUN setcap CAP_NET_BIND_SERVICE+ep /usr/local/bin/php

RUN chown -R user:user make-busy
WORKDIR /home/user/make-busy
USER user

RUN ./composer install && ./composer clear-cache

COPY etc/commit commit
COPY build/rebuild.sh build/rebuild.sh
RUN build/rebuild.sh

COPY build/run.sh ../run.sh
ENTRYPOINT [ "../run.sh" ]
