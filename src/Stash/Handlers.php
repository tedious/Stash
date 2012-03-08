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
 * Handlers contains various functions used to organize Handler classes that are available in the system.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Handlers
{
    /**
     * An array of possible cache storage data methods, with the handler class as the array value.
     *
     * @var array
     */
    protected static $handlers = array('Apc' => '\Stash\Handler\Apc',
                                       'FileSystem' => '\Stash\Handler\FileSystem',
                                       'Memcached' => '\Stash\Handler\Memcached',
                                       'MultiHandler' => '\Stash\Handler\MultiHandler',
                                       'SQLite' => '\Stash\Handler\Sqlite',
                                       'Xcache' => '\Stash\Handler\Xcache',
    );


    /**
     * Returns a list of build-in cache handlers that are also supported by this system.
     *
     * @return array Handler Name => Class Name
     */
    static function getHandlers()
    {
        $availableHandlers = array();
        foreach (self::$handlers as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            if (!in_array('Stash\Handler\HandlerInterface', class_implements($class))) {
                continue;
            }

            if($name == 'MultiHandler') {
                $availableHandlers[$name] = $class;
            } else {
                if($class::isAvailable()) {
                    $availableHandlers[$name] = $class;
                }
            }
        }

        return $availableHandlers;
    }

    static function registerHandler($name, $class)
    {
        self::$handlers[$name] = $class;
    }

    static function getHandlerClass($name)
    {
        if (!isset(self::$handlers[$name])) {
            return false;
        }

        return self::$handlers[$name];
    }

}
