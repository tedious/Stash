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
use Stash\Interfaces\DriverInterface;
use Stash\Exception\RuntimeException;

/**
 * The Predis driver is used for storing data on a Redis system. This class uses
 * the Predis library to access the Redis server.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Predis implements DriverInterface
{
    /**
     * An array of default options.
     *
     * @var array
     */
    protected $defaultOptions = array();

    /**
     * The Predis driver.
     *
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * The cache of indexed keys.
     *
     * @var array
     */
    protected $keyCache = array();

    /**
     * Initializes the driver.
     *
     * @throws RuntimeException 'Extension is not installed.'
     */
    public function __construct()
    {
        if (!static::isAvailable()) {
            throw new RuntimeException('Extension is not installed.');
        }
    }

    /**
     * The options array should be any valid Predis connection parameters.
     * See: https://github.com/nrk/predis/wiki/Connection-Parameters
     *
     * @param  array             $options
     * @throws \RuntimeException
     */
    public function setOptions(array $options = array())
    {
        if (!self::isAvailable()) {
            throw new \RuntimeException('Unable to load Predis driver without Predis dependency.');
        }

        // Merge in default values.
        $options = array_merge($this->defaultOptions, $options);

        $this->redis = new \Predis\Client($options);
    }

    /**
     * Properly close the connection.
     *
     * {@inheritdoc}
     */
    public function __destruct()
    {
        if ($this->redis instanceof \Predis\Client) {
            try {
                $this->redis->disconnect();
            } catch (\ConnectionException $e) {
                /*
                 * \Predis\Client::disconnect will throw a \ConnectionException("Redis server went away") exception if
                 * we haven't previously been able to connect to Redis or the connection has severed.
                 */
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        return unserialize($this->redis->get($this->makeKeyString($key)));
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        $store = serialize(array('data' => $data, 'expiration' => $expiration));
        if (is_null($expiration)) {
            return $this->redis->set($this->makeKeyString($key), $store);
        }

        $ttl = $expiration - time();

        // Prevent us from even passing a negative ttl'd item to redis,
        // since it will just round up to zero and cache forever.
        if ($ttl < 1) {
            return true;
        }

        $response = $this->redis->setex($this->makeKeyString($key), $ttl, $store);

        return $response == 'OK' || $response == 'QUEUED';
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->redis->flushdb();

            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal = $this->makeKeyString($key);
        $this->redis->incr($keyString); // increment index for children items
        $this->redis->del($keyReal); // remove direct item.
        $this->keyCache = array();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return class_exists('Predis\\Client');
    }

    /**
     * Turns a key array into a key string. This includes running the indexing functions used to manage the Redis
     * hierarchical storage.
     *
     * When requested the actual path, rather than a normalized value, is returned.
     *
     * @param  array  $key
     * @param  bool   $path
     * @return string
     */
    protected function makeKeyString($key, $path = false)
    {
        $key = \Stash\Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
        $pathKey = ':pathdb::';
        foreach ($key as $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::' . $keyString;
            $pathKey = md5($pathKey);

            if (isset($this->keyCache[$pathKey])) {
                $index = $this->keyCache[$pathKey];
            } else {
                $index = $this->redis->get($pathKey);
                $this->keyCache[$pathKey] = $index;
            }

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_' . $index . ':::';
        }

        return $path ? $pathKey : md5($keyString);
    }
}
