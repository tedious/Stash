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

use Stash\Handler\HandlerInterface;

/**
 * StashBox makes managing a simply cache system easier by encapsulating certain commonly used tasks. StashBox also
 * makes it easier to reuse a handler object for each Stash instance. The downside to StashBox is that it only works
 * with one handler at a time, so systems with multiple cache pools will want to use the StashManager class instead.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Box
{
    static protected $handler;


    /**
     * Takes the same arguments as the Stash->setupKey() function and returns with a new Stash object. If a handler
     * has been set for this class then it is used, otherwise the Stash object will be set to use script memory only.
     * Any Stash object set for this class uses the 'stashbox' namespace.
     *
     * @example $cache = new StashBox::getCache('permissions', 'user', '4', '2');
     *
     * @param string|array $key, $key, $key...
     * @return Stash
     */
    static function getCache()
    {
        $args = func_get_args();

        // check to see if a single array was used instead of multiple arguments
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $handler = (isset(self::$handler)) ? self::$handler : null;
        $stash = new Cache($handler, 'stashbox');

        if (count($args) > 0) {
            $stash->setupKey($args);
        }

        return $stash;
    }

    /**
     * Works exactly like the Stash->clear() function, except it can be called as a single function which will build the
     * Stash object internally, load the handler, and clear the portion of the cache pool specified all in one call.
     *
     * @param null|string|array $key, $key, $key...
     * @return bool success
     */
    static function clearCache()
    {
        $stash = self::getCache(func_get_args());
        return $stash->clear();
    }

    /**
     * Works exactly like the Stash->purge() function, except it can be called as a single function which will build the
     * Stash object internally, load the handler, and run the purge function all in one call.
     *
     * @return bool success
     */
    static function purgeCache()
    {
        $stash = self::getCache();
        return $stash->purge();
    }

    /**
     * Returns a list of all available handlers that are registered with the system.
     *
     * @return array ShortName => Class
     */
    static function getCacheHandlers()
    {
        return Handlers::getHandlers();
    }

    /**
     * Sets a handler for each Stash object created by this class. This allows the handlers to be created just once
     * and reused, making it much easier to incorporate caching into any code.
     *
     * @param HandlerInterface $handler
     */
    static function setHandler(HandlerInterface $handler)
    {
        self::$handler = $handler;
    }
}
