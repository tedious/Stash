<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash;

/**
 * DriverList contains various functions used to organize Driver classes that are available in the system.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class DriverList
{
    /**
     * An array of possible cache storage data methods, with the driver class as the array value.
     *
     * @var array
     */
    protected static $drivers = array('Apc' => '\Stash\Driver\Apc',
                                       'BlackHole' => '\Stash\Driver\BlackHole',
                                       'Composite' => '\Stash\Driver\Composite',
                                       'Ephemeral' => '\Stash\Driver\Ephemeral',
                                       'FileSystem' => '\Stash\Driver\FileSystem',
                                       'Memcache' => '\Stash\Driver\Memcache',
                                       'Redis' => '\Stash\Driver\Redis',
                                       'SQLite' => '\Stash\Driver\Sqlite',
    );

    /**
     * Returns a list of cache drivers that are also supported by this system.
     *
     * @return array Driver Name => Class Name
     */
    public static function getAvailableDrivers()
    {
        $availableDrivers = array();
        $allDrivers = self::getAllDrivers();
        foreach ($allDrivers as $name => $class) {
            if ($name == 'Composite') {
                $availableDrivers[$name] = $class;
            } else {
                if ($class::isAvailable()) {
                    $availableDrivers[$name] = $class;
                }
            }
        }

        return $availableDrivers;
    }

    /**
     * Returns a list of all registered cache drivers, regardless of system support.
     *
     * @return array Driver Name => Class Name
     */
    public static function getAllDrivers()
    {
        $driverList = array();
        foreach (self::$drivers as $name => $class) {
            if (!class_exists($class) || !in_array('Stash\Interfaces\DriverInterface', class_implements($class))) {
                continue;
            }
            $driverList[$name] = $class;
        }

        return $driverList;
    }

    /**
     * Registers a new driver.
     *
     * @param string $name
     * @param string $class
     */
    public static function registerDriver($name, $class)
    {
        self::$drivers[$name] = $class;
    }

    /**
     * Returns the driver class for a specific driver name.
     *
     * @param  string $name
     * @return bool
     */
    public static function getDriverClass($name)
    {
        if (!isset(self::$drivers[$name])) {
            return false;
        }

        return self::$drivers[$name];
    }

    /**
     * Returns a list of cache drivers that are also supported by this system.
     *
     * @deprecated Deprecated in favor of getAvailableDrivers.
     * @return array
     */
    public static function getDrivers()
    {
        return self::getAvailableDrivers();
    }
}
