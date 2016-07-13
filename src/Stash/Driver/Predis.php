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

use Predis\Client;
use Stash;

/**
 * The Redis driver is used for storing data on a Redis system. This class uses
 * the Predis package to access the Redis server.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Predis extends AbstractDriver
{
    /**
     * The Predis client.
     *
     * @var \Predis\Client
     */
    protected $predis;

    /**
     * The cache of indexed keys.
     *
     * @var array
     */
    protected $keyCache = array();

    /**
     * @param Client $predis
     */
    public function setPredis($predis)
    {
        $this->predis = $predis;
    }

    /**
     * Properly close the connection.
     */
    public function __destruct()
    {
        unset($this->predis);
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        return unserialize($this->predis->get($this->makeKeyString($key)));
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        $store = serialize(array('data' => $data, 'expiration' => $expiration));
        if (is_null($expiration)) {
            return $this->predis->set($this->makeKeyString($key), $store);
        } else {
            $ttl = $expiration - time();

            // Prevent us from even passing a negative ttl'd item to redis,
            // since it will just round up to zero and cache forever.
            if ($ttl < 1) {
                return true;
            }

            return $this->predis->setex($this->makeKeyString($key), $ttl, $store);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $cacheKeys = $this->predis->keys(":::cache*");
            foreach ($cacheKeys as $key) {
                $this->predis->del($key);
            }
            $pathDbKeys = $this->predis->keys(":pathdb::*");
            foreach ($pathDbKeys as $key) {
                $this->predis->del($key);
            }
            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal = $this->makeKeyString($key);
        $this->predis->incr($keyString); // increment index for children items
        $this->predis->del($keyReal); // remove direct item.
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
        return class_exists(Client::class, false);
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

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
