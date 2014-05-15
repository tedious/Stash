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

    public function getItem()
    {
        return false;
    }

    public function getItemIterator($keys)
    {
        return false;
    }

    public function flush()
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
}
