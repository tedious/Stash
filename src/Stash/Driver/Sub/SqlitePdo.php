<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver\Sub;

/**
* @package Stash
* @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdo extends Sqlite
{
    protected static $pdoDriver = 'sqlite';

    protected $responseCode = \PDO::FETCH_ASSOC;

    public function __construct($path, $directoryPermission, $filePermission, $busyTimeout)
    {
        parent::__construct($path, $directoryPermission, $filePermission, $busyTimeout);
        $this->responseCode = \PDO::FETCH_ASSOC;
    }

    public static function isAvailable()
    {
        $drivers = class_exists('\PDO', false) ? \PDO::getAvailableDrivers() : array();

        return in_array(static::$pdoDriver, $drivers);
    }

    protected function setTimeout($milliseconds)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $timeout = ceil($milliseconds / 1000);
        $driver->setAttribute(\PDO::ATTR_TIMEOUT, $timeout);
    }

    protected function buildDriver()
    {
        $db = new \PDO(static::$pdoDriver . ':' . $this->path);

        return $db;
    }
}
