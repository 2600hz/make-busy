FROM 2600hz/freeswitch
MAINTAINER Roman Galeev <jamhed@2600hz.com>

# override this with run --env parameters
ENV NETWORK=kazoo
ENV TYPE=auth
ENV MAKEBUSY_URL=http://makebusy.$NETWORK/gateways.php

COPY etc/freeswitch makebusy/conf
COPY build/setup-runtime.sh makebusy/setup-runtime.sh
COPY build/run-bs.sh run-bs.sh

USER root
RUN rm -rf /usr/local/freeswitch/conf \
	&& cp -a makebusy/conf /usr/local/freeswitch \
	&& chown -R freeswitch:freeswitch /usr/local/freeswitch
USER freeswitch

ENTRYPOINT [ "./run-bs.sh" ]
