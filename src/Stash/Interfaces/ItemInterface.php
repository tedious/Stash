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

use \Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;

interface ItemInterface extends CacheItemInterface
{
    /**
     * Sets the Parent Pool for the Item class to use.
     *
     * Typically called by Pool directly, and *must* be called before running caching functions.
     *
     * @param PoolInterface $driver
     */
    public function setPool(PoolInterface $driver): void;

    /**
     * Takes and sets the key and namespace.
     *
     * Typically called by Pool directly, and *must* be called before running caching functions.
     *
     * @param array       $key
     * @param string|null $namespace
     */
    public function setKey(array $key, ?string $namespace = null): void;

    /**
     * This disables any IO operations by this object, effectively preventing
     * the reading and writing of new data.
     *
     * @return bool
     */
    public function disable(): bool;

    /**
     * Returns the key as a string. This is particularly useful when the Item is
     * returned as a group of Items in an Iterator.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Clears the current Item. If hierarchical or "stackable" caching is being
     * used this function will also remove children Items.
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Returns the data retrieved from the cache. Since this can return false or
     * null as a correctly cached value, the return value should not be used to
     * determine successful retrieval of data- for that use the "isMiss()"
     * function after call this one. If no value is stored at all then this
     * function will return null.
     *
     * @return mixed
     */
    public function get(): mixed;

    /**
     * Returns true if the cached item is valid and usable.
     *
     * @return bool
     */
    public function isHit(): bool;

    /**
     * Returns true if the cached item needs to be refreshed.
     *
     * @return bool
     */
    public function isMiss(): bool;

    /**
     * Enables stampede protection by marking this specific instance of the Item
     * as the one regenerating the cache.
     *
     * @param  null $ttl
     * @return bool
     */
    public function lock(?int $ttl = null): bool;

    /**
     * Takes and stores data for later retrieval. This data can be any php data,
     * including arrays and object, except resources and objects which are
     * unable to be serialized.
     *
     * @param  mixed $value bool
     * @return self
     */
    public function set(mixed $value): static;

    /**
     * Extends the expiration on the current cached item. For some engines this
     * can be faster than storing the item again.
     *
     * @param  int|\DateInterval|null $ttl
     * @return \Stash\Item|bool
     */
    public function extend(int|\DateInterval|null $ttl = null): \Stash\Item|bool;

    /**
     * Return true if caching is disabled
     *
     * @return bool True if caching is disabled.
     */
    public function isDisabled(): bool;

    /**
     * Sets a PSR\Logger style logging client to enable the tracking of errors.
     *
     * @param  \PSR\Log\LoggerInterface $logger
     * @return bool
     */
    public function setLogger(LoggerInterface $logger): bool;

    /**
     * Returns the record's creation time or false if it isn't set
     *
     * @return \DateTime|bool
     */
    public function getCreation(): \DateTime|bool;

    /**
     * Returns the record's expiration timestamp or false if no expiration timestamp is set
     *
     * @return \DateTime
     */
    public function getExpiration(): \DateTime;

    /**
    * Sets the expiration based off of an integer or DateInterval
    *
    * @param int|\DateInterval|null $time
    * @return self
    */
    public function expiresAfter(int|\DateInterval|null $time): static;

    /**
    * Sets the expiration to a specific time.
    *
    * @param \DateTimeInterface|null $expiration
    * @return self
    */
    public function expiresAt(\DateTimeInterface|null $expiration): static;

    /**
    * Sets the expiration based off a an integer, date interval, or date
    *
    * @param int|\DateInterval|\DateTimeInterface|null $ttl An integer, date interval, or date
    * @return self
    */
    public function setTTL(int|\DateInterval|\DateTimeInterface|null $ttl = null): static;

    /**
    * Set the cache invalidation method for this item.
    *
    * @see \Stash\Invalidation
    *
    * @param int   $invalidation A Stash\Invalidation constant
    * @param mixed $arg          First argument for invalidation method
    * @param mixed $arg2         Second argument for invalidation method
    */
    public function setInvalidationMethod(int $invalidation, mixed $arg = null, mixed $arg2 = null): void;

    /**
    * Persists the Item's value to the backend storage.
    *
    * @return bool
    */
    public function save(): bool;
}
