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
 * Class SqlitePDO
 *
 * This SQLite subdriver uses PDO and the latest version of SQLite.
 *
 * @internal
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdo extends Sqlite
{
    /**
     * Output of buildDriver, used to interact with the relevant SQLite extension.
     *
     * @var \PDO
     */
    protected $driver;

    /**
     * PDO driver string, used to distinguish between SQLite versions.
     *
     * @var string
     */
    protected static $pdoDriver = 'sqlite';

    /**
     * {@inheritdoc}
     */
    protected $responseCode = \PDO::FETCH_ASSOC;

    /**
     * {@inheritdoc}
     */
    public function __construct($path, $directoryPermission, $filePermission, $busyTimeout)
    {
        parent::__construct($path, $directoryPermission, $filePermission, $busyTimeout);
        $this->responseCode = \PDO::FETCH_ASSOC;
    }

    /**
     * Checks that PDO extension is present and has the appropriate SQLite driver.
     *
     */
    public static function isAvailable()
    {
        $drivers = class_exists('\PDO', false) ? \PDO::getAvailableDrivers() : array();

        return in_array(static::$pdoDriver, $drivers);
    }

    /**
     * {@inheritdoc}
     */
    protected function setTimeout($milliseconds)
    {
        if (!($driver = $this->getDriver())) {
            return false;
        }

        $timeout = ceil($milliseconds / 1000);
        $driver->setAttribute(\PDO::ATTR_TIMEOUT, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDriver()
    {
        $db = new \PDO(static::$pdoDriver . ':' . $this->path);

        return $db;
    }
}
