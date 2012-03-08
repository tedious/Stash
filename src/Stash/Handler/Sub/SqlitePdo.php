<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Handler\Sub;

/**
* @package Stash
* @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdo extends Sqlite
{
    public function __construct($path, $directoryPermissiom, $filePermission, $busyTimeout)
    {
        $this->path = $path;
        $this->filePermissions = $filePermission;
        $this->dirPermissions = $directoryPermissiom;
        $this->busyTimeout = $busyTimeout;
        $this->responseCode = \PDO::FETCH_ASSOC;
    }

    static public function isAvailable()
    {
        $drivers = class_exists('\PDO', false) ? \PDO::getAvailableDrivers() : array();
        return in_array('sqlite', $drivers);
    }

    protected function setTimeout($milliseconds)
    {
        if (!($handler = $this->getHandler())) {
            return false;
        }

        $timeout = ceil($milliseconds / 1000);
        $handler->setAttribute(\PDO::ATTR_TIMEOUT, $timeout);
    }

    protected function buildHandler()
    {
        $db = new \PDO('sqlite:' . $this->path);
        return $db;
    }
}
