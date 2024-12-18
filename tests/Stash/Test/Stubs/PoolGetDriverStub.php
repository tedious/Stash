<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Stubs;

use Psr\Cache\CacheItemInterface;
use Stash\Interfaces\PoolInterface;
use Stash\Item;

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class PoolGetDriverStub implements PoolInterface
{
    protected $driver;

    public function setDriver(\Stash\Interfaces\DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    public function getDriver(): \Stash\Interfaces\DriverInterface
    {
        return $this->driver;
    }

    public function setItemClass(string $class): bool
    {
        return true;
    }

    public function getItem(string $key): CacheItemInterface
    {
        return new Item();
    }

    public function getItems(array $keys = array()): iterable
    {
        return [];
    }

    public function clear(): bool
    {
        return false;
    }

    public function purge(): bool
    {
        return false;
    }

    public function setNamespace(?string $namespace = null): bool
    {
        return false;
    }

    public function getNamespace(): bool|string
    {
        return false;
    }

    public function setLogger($logger): bool
    {
        return false;
    }

    public function setInvalidationMethod($invalidation, $arg = null, $arg2 = null): bool
    {
        return false;
    }

    public function hasItem($key): bool
    {
        return false;
    }

    public function commit(): bool
    {
        return false;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return false;
    }

    public function save(CacheItemInterface $item): bool
    {
        return false;
    }

    public function deleteItems(array $keys): bool
    {
        return false;
    }


    public function deleteItem($key): bool
    {
        return false;
    }
}
