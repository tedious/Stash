<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Handler;

use Stash;
use Stash\Exception\InvalidArgumentException;
use Stash\Exception\RuntimeException;

/**
 * StashMultieHandler is a wrapper around one or more StashHandlers, allowing faster caching engines with size or
 * persistance limitations to be backed up by slower but larger and more persistant caches. There are no artificial
 * limits placed on how many handlers can be staggered.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class MultiHandler implements HandlerInterface
{

    protected $handlers = array();

    /**
     * This function should takes an array which is used to pass option values to the handler.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['handlers']) || !is_array($options['handlers']) || count($options['handlers']) < 1) {
            throw new RuntimeException('One or more secondary handlers are required.');
        }

        foreach ($options['handlers'] as $handler) {
            if (!(is_object($handler) && $handler instanceof HandlerInterface)) {
                continue;
            }

            $this->handlers[] = $handler;
        }

        if (count($this->handlers) < 1) {
            throw new RuntimeException('None of the secondary handlers can be enabled.');
        }
    }

    /**
     * Empty destructor to maintain a standardized interface across all handlers.
     *
     */
    public function __destruct()
    {
    }

    /**
     * This function should return the data array, exactly as it was received by the storeData function, or false if it
     * is not present. This array should have a value for "data" and for "expiration", which should be the data the
     * main script is trying to store.
     *
     * @param $key
     * @return array
     */
    public function getData($key)
    {
        $failedHandlers = array();
        $return = false;
        foreach ($this->handlers as $handler) {
            if ($return = $handler->getData($key)) {
                $failedHandlers = array_reverse($failedHandlers);
                foreach ($failedHandlers as $failedHandler) {
                    $failedHandler->storeData($key, $return['data'], $return['expiration']);
                }

                break;
            } else {
                $failedHandlers[] = $handler;
            }
        }

        return $return;
    }

    /**
     * This function takes an array as its first argument and the expiration time as the second. This array contains two
     * items, "expiration" describing when the data expires and "data", which is the item that needs to be
     * stored. This function needs to store that data in such a way that it can be retrieved exactly as it was sent. The
     * expiration time needs to be stored with this data.
     *
     * @param array $key
     * @param array $data
     * @param $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        $handlers = array_reverse($this->handlers);
        $return = true;
        foreach ($handlers as $handler) {
            $storeResults = $handler->storeData($key, $data, $expiration);
            $return = $return && $storeResults;
        }

        return $return;
    }

    /**
     * This function should clear the cache tree using the key array provided. If called with no arguments the entire
     * cache needs to be cleared.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {
        $handlers = array_reverse($this->handlers);
        $return = true;
        foreach ($handlers as $handler) {
            $clearResults = $handler->clear($key);
            $return = $return && $clearResults;
        }

        return $return;
    }

    /**
     * This function is used to remove expired items from the cache.
     *
     * @return bool
     */
    public function purge()
    {
        $handlers = array_reverse($this->handlers);
        $return = true;
        foreach ($handlers as $handler) {
            $purgeResults = $handler->purge();
            $return = $return && $purgeResults;
        }

        return $return;
    }

    /**
     * This function checks to see if this handler is available. This always returns true because this
     * handler has no dependencies, beign a wrapper around other classes.
     *
     * @return bool true
     */
    static public function isAvailable()
    {
        return true;
    }
}
