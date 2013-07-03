#!/bin/sh

set -e

echo "***********************************"
echo "Creating Second Redis Installation."
echo "***********************************"
echo ""
echo ""


echo "Copying Configuration..."

CONFIGPATH=${TRAVIS_BUILD_DIR}/tests/travis/files/redis
sudo cp ${CONFIGPATH}/redis-server2 /etc/init.d/redis-server2
sudo cp ${CONFIGPATH}/redis-server2.conf /etc/redis/redis-server2.conf

echo "Creating Data Directory..."

sudo mkdir /var/lib/redis2
sudo chown redis:redis /var/lib/redis2


echo "Starting Second Service..."

sudo service redis-server2 start
sleep 3
echo "Finished setup of second redis server."
