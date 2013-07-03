#!/bin/sh

set -e

echo "***********************************"
echo "Creating Second Redis Installation."
echo "***********************************"
echo ""
echo ""


echo "Copying Configuration..."

CONFIGPATH=${TRAVIS_BUILD_DIR}/stash/tests/travis/files
cp ${CONFIGPATH}/redis-server2 /etc/init.d/redis-server2
cp ${CONFIGPATH}/redis-server2.conf /etc/redis/redis-server2.conf



echo "Starting Second Service..."

service redis-server2 start
echo "Finished setup of second redis server."
