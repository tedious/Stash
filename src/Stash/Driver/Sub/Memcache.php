<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver\Sub;

/**
 * @internal
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Memcache
{
    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Constructs the Memcache subdriver.
     *
     * Takes an array of servers, with array containing another array with the server, port and weight.
     *
     * array(array( '127.0.0.1', 11211, 20), array( '192.168.10.12', 11213, 80), array( '192.168.10.12', 11211, 80));
     *
     * @param array $servers
     */
    public function __construct($servers)
    {
        $memcache = new \Memcache();

        foreach ($servers as $server) {
            $host = $server[0];
            $port = isset($server[1]) ? $server[1] : 11211;
            $weight = isset($server[2]) ? (int) $server[2] : null;

            if (is_integer($weight)) {
                $memcache->addServer($host, $port, true, $weight);
            } else {
                $memcache->addServer($host, $port);
            }
        }

        $this->memcached = $memcache;
    }

    /**
     * Stores the data in memcached.
     *
     * @param  string   $key
     * @param  mixed    $value
     * @param  null|int $expire
     * @return bool
     */
    public function set($key, $value, $expire = null)
    {
        if (isset($expire) && $expire < time()) {
            return true;
        }

        return $this->memcached->set($key, array('data' => $value, 'expiration' => $expire), null, $expire);
    }

    /**
     * Retrieves the data from memcached.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        return @$this->memcached->get($key);
    }

    /**
     * This function emulates the compare and swap functionality available in the other extension. This allows
     * that functionality to be used when possible and emulated without too much issue, but for obvious reasons
     * this shouldn't be counted on to be exact.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    public function cas($key, $value)
    {
        if (($return = @$this->memcached->get($key)) !== false) {
            return $return;
        }

        $this->memcached->set($key, $value);

        return $value;
    }

    /**
     * Increments the key and returns the new value.
     *
     * @param $key
     * @return int
     */
    public function inc($key)
    {
        $this->cas($key, 0);

        return $this->memcached->increment($key);
    }

    /**
     * Flushes memcached.
     */
    public function flush()
    {
        $this->memcached->flush();
    }

    /**
     * Returns true if the Memcache extension is installed.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        return class_exists('Memcache', false);
    }
}
