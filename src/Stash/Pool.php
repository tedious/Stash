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

use Stash\Handler\Ephemeral;
use Stash\Handler\HandlerInterface;

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Pool
{
    protected $handler;


    /**
     * Takes the same arguments as the Stash->setupKey() function and returns with a new Stash object. If a handler
     * has been set for this class then it is used, otherwise the Stash object will be set to use script memory only.
     * Any Stash object set for this class uses the 'stashbox' namespace.
     *
     * @example $cache = $pool->getCache('permissions', 'user', '4', '2');
     *
     * @param string|array $key, $key, $key...
     * @return Stash\Cache
     */
    function getCache()
    {
        $args = func_get_args();

        // check to see if a single array was used instead of multiple arguments
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $handler = $this->getHandler();
        $cache = new Cache($this->handler);
        if (count($args) > 0) {
            $cache->setupKey($args);
        }

        return $cache;
    }

    /**
     * Returns a group of cache objects as an \Iterator
     *
     * Bulk lookups can often by steamlined by backend cache systems. The
     * returned iterator will contain a Cache\Item for each key passed.
     *
     * @param array $keys
     * @return \Iterator
     */
    function getCacheIterator($keys)
    {
        // temporarily cheating here by wrapping around single calls.

        $items = array();
        foreach($keys as $key)
        {
            $items[] = $this->getCache($key);
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
        return $this->getHandler()->clear();
    }

    /**
     *
     *
     * @return bool success
     */
    function purge()
    {
        return $this->getHandler()->purge();
    }

    /**
     * Sets a handler for each Stash object created by this class. This allows
     * the handlers to be created just once and reused, making it much easier to incorporate caching into any code.
     *
     * @param HandlerInterface $handler
     */
    function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    protected function getHandler()
    {
        if(!isset($this->handler))
            $this->handler = new Ephemeral();

        return $this->handler;
    }
}
