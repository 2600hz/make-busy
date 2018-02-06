FROM php:7.1-alpine
RUN wget https://github.com/lazedo/freeswitch-php-esl/archive/esl7-1.6.19.tar.gz \
	&& mkdir -p /tmp/esl \
	&& tar -xf esl7-1.6.19.tar.gz -C /tmp/esl --strip-components=1 \
	&& rm esl7-1.6.19.tar.gz \
	&& docker-php-ext-configure /tmp/esl --enable-ESL \
	&& docker-php-ext-install /tmp/esl \
        && apk --update add libstdc++ \
        && cp /tmp/esl/ESL.php `pear config-show | grep php_dir | tr -s ' ' | cut -d ' ' -f 4` \
        && echo "include_path = $(pear config-show | grep php_dir | tr -s ' ' | cut -d ' ' -f 4)" >> /usr/local/etc/php/conf.d/docker-php-ext-ESL.ini \
        && rm -r /tmp/esl

