FROM alpine
MAINTAINER 2600hz <engineering@2600hz.com>

RUN apk update && apk add freeswitch wget

# override this with run --env parameters
ENV TYPE=auth
ENV MAKEBUSY_URL=http://makebusy/gateways.php

COPY etc/freeswitch /etc/freeswitch


ENTRYPOINT [ "freeswitch", "-nonat" ]
