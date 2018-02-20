FROM 2600hz/freeswitch:alpine-edge
ARG CFG_BRANCH=master
ENV CFG_BRANCH=${CFG_BRANCH}
RUN rm -rf /etc/freeswitch \
    && mkdir /etc/freeswitch \
    && cd /etc/freeswitch \
    && curl $(echo "https://codeload.github.com/2600hz/kazoo-configs-freeswitch/tar.gz/${CFG_BRANCH}") | \
       tar -xz --strip-components=2 $(echo "kazoo-configs-freeswitch-${CFG_BRANCH}/freeswitch")
