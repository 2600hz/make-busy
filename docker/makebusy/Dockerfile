FROM php:5.6.27-apache
MAINTAINER Roman Galeev <jamhed@2600hz.com>

USER root
WORKDIR /root

ARG REPO=https://github.com/2600hz/make-busy.git
ARG COMMIT=HEAD

COPY build/setup-os.sh build/setup-os.sh
RUN build/setup-os.sh

COPY etc/.ssh .ssh

COPY build/setup.sh build/setup.sh
COPY build/rebuild.sh build/rebuild.sh
RUN build/setup.sh

COPY build/run.sh build/run.sh
COPY build/run-test.sh run-test.sh
COPY build/run-test-verbose.sh run-test-verbose.sh

COPY etc/config.json etc/config.json
COPY build/setup-bs.sh build/setup-bs.sh
RUN build/setup-bs.sh

COPY etc/.vimrc /root

COPY etc/commit commit
RUN build/rebuild.sh
