<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Interfaces;

/**
 * Stash Drivers are the engines behind the Stash library. These classes handle the low level operations - retrieving,
 * storing and deleting items in the persistant cache pool. By creating new drivers developers can add new caching
 * methods to their applications with extremely minimal changes to their existing code base. This interface defines the
 * standard for those drivers and all of them are required to implement it. When writing new cache storage engines this
 * is the place to start.
 *
 * A few important notes when implementing this interface:
 *
 * * Unlike with the Stash class itself, instances of drivers are meant to be reused over and over again, allowing them
 * to avoid the overhead of repeatedly opening and closing resources. There are times when multiple instances of an
 * engine will be created though (where a developer wants two separate cache pools for example) so static caching
 * techniques should be avoided- the StashDriver will be kept open and reused by the developers, so instance properties
 * will persist in a useful way.
 *
 * * Each storage engine must be able to handle multiple requests with the same object, meaning functions like
 * getData can be called multiple times in sequence while storeData may be mixed in at random frequencies, all using
 * different keys.
 *
 * * Keys are passed as arrays that represent a hierarchical 'location' where the cached data is virtually stored, with
 *  each item in the array being a deeper level of that hierarchy. In other words $key[0] is the root of the cache tree
 *  and $key[2] is the child of $key[0] + $key[1]. Each level can be both a piece of data and a parent location, which
 *  is particularly important for purge and delete operations.
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */

interface DriverInterface
{
    /**
     * Returns the previously stored data as well as its expiration date in an associative array. This array contains
     * two keys - a 'data' key and an 'expiration' key. The 'data' key should be exactly the same as the value passed to
     * storeData.
     *
     * @param  array $key
     * @return array
     */
    public function getData($key);

    /**
     * Takes in data from the exposed Stash class and stored it for later retrieval.
     *
     * *The first argument is an array which should map to a specific, unique location for that array, This location
     * should also be able to handle recursive deletes, where the removal of an item represented by an identical, but
     * truncated, key causes all of the 'children' keys to be removed.
     *
     * *The second argument is the data itself. This is an array which contains the raw storage as well as meta data
     * about the data. The meta data can be ignored or used by the driver but entire data parameter must be retrievable
     * exactly as it was placed in.
     *
     * *The third parameter is the expiration date of the item as a timestamp. This should also be stored, as it is
     * needed by the getData function.
     *
     * @param  array $key
     * @param  mixed $data
     * @param  int   $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration);

    /**
     * Clears the cache tree using the key array provided as the key. If called with no arguments the entire cache gets
     * cleared.
     *
     * @param  null|array $key
     * @return bool
     */
    public function clear($key = null);

    /**
     * Remove any expired code from the cache. For some drivers this can just return true, as their underlying engines
     * automatically take care of time based expiration (apc or memcache for example). This function should also be used
     * for other clean up operations that the specific engine needs to handle. This function is generally called outside
     * of user requests as part of a maintenance check, so it is okay if the code in this function takes some time to
     * run,
     *
     * @return bool
     */
    public function purge();

    /**
     * Returns whether the driver is able to run in the current environment or not. Any system checks - such as making
     * sure any required extensions are missing - should be done here. This is a general check; if any instance of this
     * driver can be used in the current environment it should return true.
     *
     * @return bool
     */
    public static function isAvailable();

    /**
     * Returns whether the driver is able to run in the current environment or not. Any system checks - such as making
     * sure any required extensions are missing - should be done here. This is a general check; if any instance of this
     * driver can be used in the current environment it should return true.
     *
     * @return bool
     */
    public function isPersistent();
}
