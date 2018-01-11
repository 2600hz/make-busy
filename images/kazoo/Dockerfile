FROM 2600hz/kazoo-build as build
ARG COMMIT
RUN mkdir kazoo \
    && cd kazoo \
    && curl -L https://api.github.com/repos/2600hz/kazoo/tarball/$COMMIT | tar xz --strip-components=1 \
   && make \
   && make tar-release

FROM alpine
RUN apk --update add ncurses bash libstdc++ openssl expat
ENV PATH=$PATH:/opt/kazoo/bin

ENV KAZOO_ZONE=local
ENV KAZOO_NODE=apps
ENV KAZOO_COOKIE=change_me

ENV RELX_REPLACE_OS_VARS=true
ENV TZ=UTC
ENV CODE_LOADING_MODE=interactive

COPY --from=build /kazoo/_rel/kazoo/kazoo-4.0.0.tar.gz /opt/
RUN mkdir /opt/kazoo && tar -xf /opt/kazoo-4.0.0.tar.gz -C /opt/kazoo && rm /opt/kazoo-4.0.0.tar.gz
ADD config.ini /etc/kazoo/
ADD vm.args /etc/kazoo
#/opt/kazoo/releases/4.0.0/
RUN ln /opt/kazoo/lib/sup-4.0.0/priv/sup /opt/kazoo/bin/sup

CMD ["kazoo", "foreground"]

