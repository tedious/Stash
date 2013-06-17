<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver;

use Stash;

/**
 * The Redis driver is used for storing data on a Redis system. This class uses
 * the PhpRedis extension to access the Redis server.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Redis implements DriverInterface
{

    /**
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {

    }

    /**
     *
     *
     * @param array $key
     * @return array
     */
    public function getData($key)
    {
        return array('data' => $data, 'expiration' => $expiration);
    }

    /**
     *
     *
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {

    }

    /**
     * Clears the cache tree using the key array provided as the key. If called with no arguments the entire cache gets
     * cleared.
     *
     * @param null|array $key
     * @return bool
     */
    public function clear($key = null)
    {

    }

    /**
     *
     * @return bool
     */
    public function purge()
    {
        return true;
    }

    /**
     *
     *
     * @return bool
     */
    static public function isAvailable()
    {
        return class_exists('Redis', false);
    }

}
