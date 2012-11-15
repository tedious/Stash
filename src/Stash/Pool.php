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
    protected $isDisabled = false;

    /**
     * The constructor takes a Driver class which is used for persistent
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
     *
     * @example $cache = $pool->getItem('permissions', 'user', '4', '2');
     *
     * @param string|array $key, $key, $key...
     * @return Stash\Item
     */
    function getItem()
    {
        $args = func_get_args();
        $argCount = count($args);

        if ($argCount < 1) {
            throw new InvalidArgumentException('Item constructor requires valid key.');
        }
        // check to see if a single array was used instead of multiple arguments
        if ($argCount == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $driver = $this->getDriver();
        $cache = new Item($this->driver, $args);

        if($this->isDisabled)
            $cache->disable();

        return $cache;
    }

    /**
     * Returns a group of cache objects as an \Iterator
     *
     * Bulk lookups can often by streamlined by backend cache systems. The
     * returned iterator will contain a Stash\Item for each key passed.
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
        if($this->isDisabled)
            return false;

        try{
            $results = $this->getDriver()->clear();
        }catch(\Exception $e){
            $this->isDisabled = true;
            return false;
        }
        return $results;
    }

    /**
     * The Purge function allows drivers to perform basic maintenance tasks,
     * such as removing stale or expired items from storage. Not all drivers
     * need this, as many interact with systems that handle that automatically.
     *
     * It's important that this function is not called from inside a normal
     * request, as the maintenance tasks this allows can occasionally take some
     * time.
     *
     * @return bool success
     */
    function purge()
    {
        if($this->isDisabled)
            return false;

        try{
            $results = $this->getDriver()->purge();
        }catch(\Exception $e){
            $this->isDisabled = true;
            return false;
        }
        return $results;
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

    function getDriver()
    {
        if(!isset($this->driver))
            $this->driver = new Ephemeral();

        return $this->driver;
    }
}
