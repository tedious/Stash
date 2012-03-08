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
use Stash\Handler\Ephemeral;
use Stash\Exception\InvalidArgumentException;

/**
 * StashManager is a collection of static functions used to make certain repetitive tasks easier by consilidating their
 * steps. Unlike the StashBox class StashManager can work with multiple distinct cache pools.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Manager
{
    static protected $handlers = array();


    /**
     * Takes the same arguments as the Stash->setupKey() function and returns with a new Stash object. If a handler
     * has been set for this class then it is used, otherwise the Stash object will be set to use script memory only.
     * Any Stash object set for this class uses a custom namespace.
     * The first argument must be the name of the specific cache being used, which should correspond to the name of a
     * handler passed in through the setHandler function- if using a one cache system please check out StashBox instead.
     *
     * @example $cache = new StashBox::getCache('Primary Cache', 'permissions', 'user', '4', '2');
     *
     * @param string $name
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

        if (count($args) < 1) {
            throw new InvalidArgumentException('getCache function requires a cache name.');
        }

        $name = array_shift($args);

        // Check to see if keys were passed as an extended argument or a single array
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        if (!isset(self::$handlers[$name])) {
            self::$handlers[$name] = new Ephemeral();
        }

        $stash = new Cache(self::$handlers[$name]);

        if (count($args) > 0) {
            $stash->setupKey($args);
        }

        return $stash;
    }


    /**
     * Works like the Stash->clear() function, except it can be called as a single function which will build the
     * Stash object internally, load the handler, and clear the portion of the cache pool specified all in one call.
     * The first argument must be the name of the specific cache being used, which should correspond to the name of a
     * handler passed in through the setHandler function- if using a one cache system please check out StashBox instead.
     *
     * @param string $name The name of the stored cache item.
     * @param null|string|array $key, $key, $key...
     * @return bool success
     */
    static function clearCache()
    {
        $stash = self::getCache(func_get_args());
        return $stash->clear();
    }

    /**
     * Works like the Stash->purge() function, except it can be called as a single function which will build the
     * Stash object internally, load the handler, and run the purge function all in one call.
     * The first argument must be the name of the specific cache being used, which should correspond to the name of a
     * handler passed in through the setHandler function- if using a one cache system please check out StashBox instead.
     *
     * @param string $name Specific cache to purge
     * @return bool success
     */
    static function purgeCache($name)
    {
        $stash = self::getCache($name);
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
     * and reused, making it much easier to incorporate caching into any code. The name used for this handler should
     * match the one used by the other cache items in order to reuse this handler.
     *
     * @param string $name The label for the handler being passed
     * @param HandlerInterface $handler
     */
    static function setHandler($name, HandlerInterface $handler)
    {
        if (!isset($handler)) {
            $handler = false;
        }
        self::$handlers[$name] = $handler;
    }

}
