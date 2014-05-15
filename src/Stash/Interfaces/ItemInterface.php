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

interface ItemInterface
{

    /**
     * Sets the Parent Pool for the Item class to use.
     *
     * Typically called by Pool directly, and *must* be called before running caching functions.
     *
     * @param PoolInterface $driver
     */
    public function setPool(PoolInterface $driver);

    /**
     * Takes and sets the key and namespace.
     *
     * Typically called by Pool directly, and *must* be called before running caching functions.
     *
     * @param array       $key
     * @param string|null $namespace
     */
    public function setKey($key, $namespace = null);

    /**
     * This disables any IO operations by this object, effectively preventing
     * the reading and writing of new data.
     *
     * @return bool
     */
    public function disable();

    /**
     * Returns the key as a string. This is particularly useful when the Item is
     * returned as a group of Items in an Iterator.
     *
     * @return string|bool Returns false if no key is set.
     */
    public function getKey();

    /**
     * Clears the current Item. If hierarchical or "stackable" caching is being
     * used this function will also remove children Items.
     *
     * @return bool
     */
    public function clear();

    /**
     * Returns the data retrieved from the cache. Since this can return false or
     * null as a correctly cached value, the return value should not be used to
     * determine successful retrieval of data- for that use the "isMiss()"
     * function after call this one. If no value is stored at all then this
     * function will return null.
     *
     * @param  int        $invalidation
     * @param  null       $arg
     * @param  null       $arg2
     * @return mixed|null
     */
    public function get($invalidation = 0, $arg = null, $arg2 = null);

    /**
     * Returns true if the cached item needs to be refreshed.
     *
     * @return bool
     */
    public function isMiss();

    /**
     * Enables stampede protection by marking this specific instance of the Item
     * as the one regenerating the cache.
     *
     * @param  null $ttl
     * @return bool
     */
    public function lock($ttl = null);

    /**
     * Takes and stores data for later retrieval. This data can be any php data,
     * including arrays and object, except resources and objects which are
     * unable to be serialized.
     *
     * @param  mixed              $data bool
     * @param  int|\DateTime|null $ttl  Int is time (seconds), DateTime a future expiration date
     * @return bool               Returns whether the object was successfully stored or not.
     */
    public function set($data, $ttl = null);

    /**
     * Extends the expiration on the current cached item. For some engines this
     * can be faster than storing the item again.
     *
     * @param  null $ttl
     * @return bool
     */
    public function extend($ttl = null);

    /**
     * Return true if caching is disabled
     *
     * @return bool True if caching is disabled.
     */
    public function isDisabled();

    /**
     * Return true if caching is disabled
     *
     * @param  \PSR\Log\LoggerInterface $logger
     * @return bool
     */
    public function setLogger($logger);

    /**
     * Returns the record's creation time or false if it isn't set
     *
     * @return \DateTime
     */
    public function getCreation();

    /**
     * Returns the record's expiration timestamp or false if no expiration timestamp is set
     *
     * @return \DateTime
     */
    public function getExpiration();
}
