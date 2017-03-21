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

use Stash\Interfaces\PoolInterface;
use Stash\Interfaces\DriverInterface;

/**
 *
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
trait PoolTrait
{
    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * Returns the Pool
     *
     * @return PoolInterface
     */
    public function getPool()
    {
        if (!$this->pool) {
            $this->pool = new Pool;
        }

        return $this->pool;
    }

    /**
     * Sets the Pool
     *
     * @param PoolInterface $pool
     *
     * @return $this
     */
    public function setPool(PoolInterface $pool = null)
    {
        $this->pool = $pool;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setItemClass($class)
    {
        return $this->getPool()->setItemClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem()
    {
        $pool = $this->getPool();

        return call_user_func_array(array($pool, 'getItem'), func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getItemIterator($keys)
    {
        return $this->getPool()->getItemIterator($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->getPool()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        return $this->getPool()->purge();
    }

    /**
     * {@inheritdoc}
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->getPool()->setDriver($driver);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriver()
    {
        return $this->getPool()->getDriver();
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace = null)
    {
        return $this->getPool()->setNamespace($namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return $this->getPool()->getNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger($logger)
    {
        return $this->getPool()->setLogger($logger);
    }
}
