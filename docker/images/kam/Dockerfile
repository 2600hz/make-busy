FROM alpine as build
ARG TOKEN
ARG PKG_VERSION=5.1.2
ARG PKG_REL=0
ARG PKG_RC=rc0

RUN    apk update \
    && apk add alpine-sdk \
    && mkdir -p /var/cache/distfiles \
    && chmod a+w /var/cache/distfiles \
    && adduser -D kamailio \
    && addgroup kamailio abuild
USER kamailio
WORKDIR /home/kamailio
ENV TOKEN=${TOKEN}
ENV PKG_VERSION=${PKG_VERSION}
ENV PKG_REL=${PKG_REL}
ENV PKG_RC=${PKG_RC}
RUN    abuild-keygen -a -i \
    && git clone https://github.com/lazedo/kamailio-docker-alpine.git \
    && cd kamailio-docker-alpine \
    && abuild checksum \
    && abuild -r

FROM alpine as kamailio
COPY --from=build /home/kamailio/packages/kamailio/x86_64/* /apks/x86_64/
RUN echo -e "/apks\n$(cat /etc/apk/repositories)" > /etc/apk/repositories \
    && apk add --update --allow-untrusted \
         bash curl iproute2 \
         kamailio kamailio-db kamailio-json kamailio-presence \
         kamailio-kazoo kamailio-db_kazoo \
         kamailio-debugger kamailio-extras \
         kamailio-outbound kamailio-websocket \
         kamailio-tls kamailio-utils kamailio-uuid

CMD ["kamailio", "-DD", "-E"]
