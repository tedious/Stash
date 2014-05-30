#!/bin/sh

set -e


echo "**************************"
echo "Setting up PHP Extensions."
echo "**************************"
echo ""
echo "PHP Version: $TRAVIS_PHP_VERSION"

if [ "$TRAVIS_PHP_VERSION" = "hhvm" ] || [ "$TRAVIS_PHP_VERSION" = "hhvm-nightly" ]; then
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
    rm -Rf phpredis
    echo "Finished installing phpredis extension."

    echo ""
    echo "******************************"
    echo "Installing uopz extension if possible (PHP >=5.4)."
    echo "******************************"
    set +e
    pecl install uopz
    set -e
    echo "Finished installing uopz extension."

    echo ""
    echo "*********************"
    echo "Updating php.ini file"
    echo "*********************"
    echo ""
    echo ""
    phpenv config-add tests/travis/php_extensions.ini

fi