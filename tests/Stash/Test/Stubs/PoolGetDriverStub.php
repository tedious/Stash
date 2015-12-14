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

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class PoolGetDriverStub implements PoolInterface
{
    protected $driver;

    public function setDriver(\Stash\Interfaces\DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function setItemClass($class)
    {
        return true;
    }

    public function getItem($key)
    {
        return false;
    }

    public function getItems(array $keys = array())
    {
        return false;
    }

    public function clear()
    {
        return false;
    }

    public function purge()
    {
        return false;
    }

    public function setNamespace($namespace = null)
    {
        return false;
    }

    public function getNamespace()
    {
        return false;
    }

    public function setLogger($logger)
    {
        return false;
    }

    public function hasItem($key)
    {
        return false;
    }

    public function commit()
    {
        return false;
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        return false;
    }

    public function save(CacheItemInterface $item)
    {
        return false;
    }

    public function deleteItems(array $keys)
    {
        return false;
    }


    public function deleteItem($key)
    {
        return false;
    }
}
