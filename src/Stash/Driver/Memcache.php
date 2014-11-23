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
use Stash\Exception\RuntimeException;
use Stash\Driver\Sub\Memcache as SubMemcache;
use Stash\Driver\Sub\Memcached as SubMemcached;
use Stash\Interfaces\DriverInterface;

/**
 * Memcache is a wrapper around the popular memcache server. Memcache supports both memcache php
 * extensions and allows access to all of their options as well as all Stash features (including hierarchical caching).
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Memcache implements DriverInterface
{
    /**
     * Memcache subdriver used by this class.
     *
     * @var SubMemcache|SubMemcached
     */
    protected $memcache;

    /**
     * Cache of calculated keys.
     *
     * @var array
     */
    protected $keyCache = array();

    /**
     * Timestamp of last time the key cache was updated.
     *
     * @var int timestamp
     */
    protected $keyCacheTime = 0;

    /**
     * Limit
     *
     * @var int seconds
     */
    protected $keyCacheTimeLimit = 1;

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
     *
     * * servers - An array of servers, with each server represented by its own array (array(host, port, [weight])). If
     * not passed the default is array('127.0.0.1', 11211).
     *
     * * extension - Which php extension to use, either 'memcache' or 'memcache'. Defaults to memcache with memcache
     * as a fallback.
     *
     * * Options can be passed to the "memcache" driver by adding them to the options array. The memcache extension
     * defined options using constants, ie Memcached::OPT_*. By passing in the * portion ('compression' for
     * Memcached::OPT_COMPRESSION) and its respective option. Please see the php manual for the specific options
     * (http://us2.php.net/manual/en/memcache.constants.php)
     *
     *
     * @param array $options
     *
     * @throws RuntimeException
     */
    public function setOptions(array $options = array())
    {
        if (!isset($options['servers'])) {
            $options['servers'] = array('127.0.0.1', 11211);
        }
        $servers = $this->normalizeServerConfig($options['servers']);

        if (!isset($options['extension'])) {
            $options['extension'] = 'any';
        }

        if (isset($options['keycache_limit']) && is_numeric($options['keycache_limit'])) {
            $this->keyCacheTimeLimit = $options['keycache_limit'];
        }

        $extension = strtolower($options['extension']);

        if (class_exists('Memcached', false) && $extension != 'memcache') {
            $this->memcache = new SubMemcached($servers, $options);
        } elseif (class_exists('Memcache', false) && $extension != 'memcached') {
            $this->memcache = new SubMemcache($servers);
        } else {
            throw new RuntimeException('No memcache extension available.');
        }
    }

    protected function normalizeServerConfig($servers)
    {
        if (is_scalar($servers[0])) {
            $servers = array($servers);
        }

        $normalizedServers = array();
        foreach ($servers as $server) {
            $host = '127.0.0.1';
            if (isset($server['host'])) {
                $host = $server['host'];
            } elseif (isset($server[0])) {
                $host = $server[0];
            }

            $port = '11211';
            if (isset($server['port'])) {
                $port = $server['port'];
            } elseif (isset($server[1])) {
                $port = $server[1];
            }

            $weight = null;
            if (isset($server['weight'])) {
                $weight = $server['weight'];
            } elseif (isset($server[2])) {
                $weight = $server[2];
            }
            $normalizedServers[] = array($host, $port, $weight);
        }

        return $normalizedServers;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        return $this->memcache->get($this->makeKeyString($key));
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        return $this->memcache->set($this->makeKeyString($key), $data, $expiration);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        $this->keyCache = array();
        if (is_null($key)) {
            $this->memcache->flush();
        } else {
            $keyString = $this->makeKeyString($key, true);
            $this->memcache->inc($keyString);
            $this->keyCache = array();
            $this->makeKeyString($key);
        }
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
     * Turns a key array into a key string. This includes running the indexing functions used to manage the memcached
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

        $keyString = ':cache:::';
        $pathKey = ':pathdb::';
        $time = microtime(true);
        if (($time - $this->keyCacheTime) >= $this->keyCacheTimeLimit) {
            $this->keyCacheTime = $time;
            $this->keyCache = array();
        }

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
                $index = $this->memcache->cas($pathKey, 0);
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
    public static function isAvailable()
    {
        return (SubMemcache::isAvailable() || SubMemcached::isAvailable());
    }
}
