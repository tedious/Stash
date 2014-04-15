#/usr/bin/env/sh
set -e

echo 'Running unit tests.'
phpunit --verbose --coverage-clover build/logs/clover.xml

echo ''
echo ''
echo ''
echo 'Testing for Coding Styling Compliance.'
echo 'All code should follow PSR standards.'
./vendor/fabpot/php-cs-fixer/php-cs-fixer fix ./ --level="all" -vv --dry-run