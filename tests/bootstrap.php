<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('TESTING', true);// this is basically used by the StashArray driver to decide if "isEnabled()" should return
                        // true, since the Array driver is not meant for production use, just testing. We should not
                        // use this anywhere else in the project since that would defeat the point of testing.
error_reporting(-1);

$filename = __DIR__ .'/../vendor/autoload.php';

if (!file_exists($filename)) {
    echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~" . PHP_EOL;
    echo "You need to execute `composer install` before running the tests." . PHP_EOL;
    echo "(vendors are required for test execution)" . PHP_EOL;
    echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~" . PHP_EOL . PHP_EOL;
    $filename = __DIR__ .'/../autoload.php';
}

require_once $filename;
