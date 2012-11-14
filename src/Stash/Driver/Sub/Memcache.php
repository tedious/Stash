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

use Stash\Exception\RuntimeException;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Memcache
{
    /**
     * @var Memcached
     */
    protected $memcached;


    public function initialize($servers, array $options = array())
    {
        $memcache = new \Memcache();

        foreach ($servers as $server) {
            $host = $server[0];
            $port = isset($server[1]) ? $server[1] : 11211;
            $weight = isset($server[2]) ? (int)$server[2] : null;

            if (is_integer($weight)) {
                $memcache->addServer($host, $port, true, $weight);
            } else {
                $memcache->addServer($host, $port);
            }
        }

        $this->memcached = $memcache;
    }

    public function set($key, $value, $expire = null)
    {
        if(isset($expire) && $expire < time()) {
            return true;
        }
        return $this->memcached->set($key, array('data' => $value, 'expiration' => $expire), null, $expire);
    }

    public function get($key)
    {
        return @$this->memcached->get($key);
    }

    public function cas($key, $value)
    {
        if (($return = @$this->memcached->get($key)) !== false) {
            return $return;
        }

        $this->memcached->set($key, $value);
        return $value;
    }

    public function inc($key)
    {
        $this->cas($key, 0);
        return $this->memcached->increment($key);
    }

    public function flush()
    {
        $this->memcached->flush();
    }

    static public function isAvailable()
    {
        return class_exists('Memcache', false);
    }
}
