from alpine as build
RUN    apk update \
    && apk add alpine-sdk vim swig \
    && ln `which swig` `which swig`3.0 \
    && mkdir -p /var/cache/distfiles \
    && chmod a+w /var/cache/distfiles \
    && adduser -D freeswitch \
    && addgroup freeswitch abuild
USER freeswitch
WORKDIR /home/freeswitch
RUN    abuild-keygen -a -i \
    && git clone https://github.com/lazedo/freeswitch-docker-alpine.git \
    && cd freeswitch-docker-alpine \
    && abuild checksum \
    && abuild -r

from alpine as freeswitch
COPY --from=build /home/freeswitch/packages/freeswitch/x86_64/freeswitch-1.9.0-r0.apk /
RUN apk update \
    && apk add /freeswitch-1.9.0-r0.apk --allow-untrusted \
    && rm freeswitch-1.9.0-r0.apk

ENTRYPOINT ["freeswitch", "-nonat"]


