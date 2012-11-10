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

    protected $disabled = false;

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
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['servers'])) {
            $options['servers'] = array('127.0.0.1', 11211);
        }

        if (is_scalar($options['servers'][0])) {
            $servers = array($options['servers']);
        } else {
            $servers = $options['servers'];
        }

        if (!isset($options['extension'])) {
            $options['extension'] = 'any';
        }

        $extension = strtolower($options['extension']);

        if (class_exists('Memcached', false) && $extension != 'memcache') {
            $this->memcache = new SubMemcached();
        } elseif (class_exists('Memcache', false) && $extension != 'memcached') {
            $this->memcache = new SubMemcache();
        } else {
            throw new RuntimeException('No memcache extension available.');
        }

        $this->memcache->initialize($servers, $options);
    }

    /**
     * Empty destructor to maintain a standardized interface across all drivers.
     *
     */
    public function __destruct()
    {
    }

    /**
     *
     * @return array
     */
    public function getData($key)
    {
        return $this->memcache->get($this->makeKeyString($key));
    }

    /**
     *
     * @param array $key
     * @param array $data
     * @param int $expiration
     * @return bool
     */
    public function storeData($key, $data, $expiration)
    {
        return $this->memcache->set($this->makeKeyString($key), $data, $expiration);
    }

    /**
     *
     * @param null|array $key
     * @return bool
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
     *
     * @return bool
     */
    public function purge()
    {
        return true;
    }

    protected function makeKeyString($key, $path = false)
    {
        // array(name, sub);
        // a => name, b => sub;

        $key = \Stash\Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
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

    static public function isAvailable()
    {
        return (SubMemcache::isAvailable() || SubMemcached::isAvailable());
    }
}
