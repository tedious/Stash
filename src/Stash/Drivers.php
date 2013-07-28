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
 * Drivers contains various functions used to organize Driver classes that are available in the system.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Drivers
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
                                       'Xcache' => '\Stash\Driver\Xcache',
    );

    /**
     * Returns a list of build-in cache drivers that are also supported by this system.
     *
     * @return array Driver Name => Class Name
     */
    public static function getDrivers()
    {
        $availableDrivers = array();
        foreach (self::$drivers as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            if (!in_array('Stash\Interfaces\DriverInterface', class_implements($class))) {
                continue;
            }

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

    public static function registerDriver($name, $class)
    {
        self::$drivers[$name] = $class;
    }

    public static function getDriverClass($name)
    {
        if (!isset(self::$drivers[$name])) {
            return false;
        }

        return self::$drivers[$name];
    }

}
