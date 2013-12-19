#!/bin/sh

set -e


echo "**************************"
echo "Setting up PHP Extensions."
echo "**************************"
echo ""
echo "PHP Version: $TRAVIS_PHP_VERSION"

if [ "$TRAVIS_PHP_VERSION" == "hhvm" ]; then
    echo "Unable to install php extensions on current system"

else

    echo ""
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

fi