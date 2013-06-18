#!/bin/sh

echo "Install phpredis extension." \
    && git clone git://github.com/nicolasff/phpredis.git \
    && cd phpredis \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && cd .. \
    && echo "Finished installing phpredis extension."
