#!/bin/sh

set -e


echo "**************************"
echo "Setting up PHP Extensions."
echo "**************************"
echo ""
echo "PHP Version: $TRAVIS_PHP_VERSION"
echo ""
echo "Update Pecl"
pecl channel-update pecl.php.net

echo ""
echo "******************************"
echo "Installing apcu extension"
echo "******************************"
set +e
printf "yes\n" | pecl install apcu
set -e
echo "Finished installing apcu extension."


echo ""
echo "******************************"
echo "Installing memcache extension"
echo "******************************"
set +e
sudo apt-get -y install unzip zlib1g-dev
wget https://github.com/websupport-sk/pecl-memcache/archive/NON_BLOCKING_IO_php7.zip
unzip NON_BLOCKING_IO_php7.zip
cd pecl-memcache-NON_BLOCKING_IO_php7
phpize
./configure --enable-memcache
make
sudo make install
cd ..
rm -Rf pecl-memcache-NON_BLOCKING_IO_php7
rm -Rf NON_BLOCKING_IO_php7.zip
set -e
echo "Finished installing memcache extension."


echo ""
echo "******************************"
echo "Installing memcached extension"
echo "******************************"
set +e
echo "Installing libmemcached-dev"
sudo apt-get -y install libmemcached-dev
printf "no --disable-memcached-sasl\n"  | pecl install memcached
set -e
echo "Finished installing memcached extension."


echo ""
echo "******************************"
echo "Installing phpredis extension."
echo "******************************"
echo ""
echo ""
echo "Downloading..."
git clone git://github.com/phpredis/phpredis.git
echo "Configuring..."
cd phpredis
phpize
./configure
echo "Installing..."
make
make install
cd ..
rm -Rf phpredis
echo "Finished installing phpredis extension."


echo ""
echo "******************************"
echo "Installing uopz extension."
echo "******************************"
set +e
pecl install uopz
set -e
echo "Finished installing uopz extension."


if [ -f "tests/travis/php_extensions.ini" ]
then
  echo ""
  echo "*********************"
  echo "Updating php.ini file"
  echo "*********************"
  echo ""
  echo ""
  phpenv config-add "tests/travis/php_extensions.ini"
fi
