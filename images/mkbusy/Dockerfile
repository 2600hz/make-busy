FROM 2600hz/php-esl
MAINTAINER 2600hz <engineering@2600hz.com>
ARG REPO=https://github.com/2600hz/make-busy.git
RUN  apk --update add sudo git unzip libcap bash jq \
     && adduser -D -s /bin/bash user \
     && echo "user ALL=(ALL) NOPASSWD: ALL" > /etc/sudoers.d/user \
     && chmod 0440 /etc/sudoers.d/user
WORKDIR /home/user
USER user
RUN  git clone $REPO ./make-busy \
     && cd make-busy \
     && git checkout -b some-fixes origin/some-fixes \
     && ./composer install \
     && ./composer clear-cache
RUN sudo setcap CAP_NET_BIND_SERVICE+ep /usr/local/bin/php
WORKDIR /home/user/make-busy
ADD config.json /home/user/make-busy
ENTRYPOINT ["php", "-S", "0.0.0.0:80"]

