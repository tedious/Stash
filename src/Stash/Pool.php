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

use Stash\Driver\Ephemeral;
use Stash\Driver\DriverInterface;

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Pool
{
    protected $driver;

    /**
     * The constructor takes a Driver class which is used for persistant
     * storage. If no driver is provided then the Ephemeral driver is used by
     * default.
     *
     * @param DriverInterface $driver
     */
    function __construct(DriverInterface $driver = null)
    {
        if (isset($driver)) {
            $this->setDriver($driver);
        }
    }

    /**
     * Takes the same arguments as the Stash->setupKey() function and returns with a new Stash object. If a driver
     * has been set for this class then it is used, otherwise the Stash object will be set to use script memory only.
     * Any Stash object set for this class uses the 'stashbox' namespace.
     *
     * @example $cache = $pool->getItem('permissions', 'user', '4', '2');
     *
     * @param string|array $key, $key, $key...
     * @return Stash\Cache
     */
    function getItem()
    {
        $args = func_get_args();

        // check to see if a single array was used instead of multiple arguments
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $driver = $this->getDriver();
        $cache = new Item($this->driver);
        if (count($args) > 0) {
            $cache->setupKey($args);
        }

        return $cache;
    }

    /**
     * Returns a group of cache objects as an \Iterator
     *
     * Bulk lookups can often by streamlined by backend cache systems. The
     * returned iterator will contain a Cache\Item for each key passed.
     *
     * @param array $keys
     * @return \Iterator
     */
    function getItemIterator($keys)
    {
        // temporarily cheating here by wrapping around single calls.

        $items = array();
        foreach($keys as $key)
        {
            $items[] = $this->getItem($key);
        }

         return new \ArrayIterator($items);
    }

    /**
     * Empties the entire cache pool of all items.
     *
     * @return bool success
     */
    function flush()
    {
        return $this->getDriver()->clear();
    }

    /**
     *
     *
     * @return bool success
     */
    function purge()
    {
        return $this->getDriver()->purge();
    }

    /**
     * Sets a driver for each Stash object created by this class. This allows
     * the drivers to be created just once and reused, making it much easier to incorporate caching into any code.
     *
     * @param DriverInterface $driver
     */
    function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    protected function getDriver()
    {
        if(!isset($this->driver))
            $this->driver = new Ephemeral();

        return $this->driver;
    }
}
