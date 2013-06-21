#!/bin/sh

set -e

echo "******************************"
echo "Installing phpredis extension."
echo "******************************"
echo ""
echo ""
echo "Downloading..."
git clone git://github.com/nicolasff/phpredis.git
echo "Configuring..."
cd phpredis
phpize
./configure
echo "Installing..."
make
make install
cd ..
echo "Finished installing phpredis extension."
