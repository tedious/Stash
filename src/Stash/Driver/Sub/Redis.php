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

class Redis
{
    protected $redis;

    protected $redisArrayOptionNames = array(
        "previous",
        "function",
        "distributor",
        "index",
        "autorehash",
        "pconnect",
        "retry_interval",
        "lazy_connect",
        "connect_timeout",
    );

    public function __construct(array $servers, array $options = array())
    {
        // this will have to be revisited to support multiple servers, using
        // the RedisArray object. That object acts as a proxy object, meaning
        // most of the class will be the same even after the changes.

        if (count($servers) == 1) {
            $server = $servers[0];
            $redis = new \Redis();

            if (isset($server['socket']) && $server['socket']) {
                $redis->connect($server['socket']);
            } else {
                $port = isset($server['port']) ? $server['port'] : 6379;
                $ttl = isset($server['ttl']) ? $server['ttl'] : 0.1;
                $redis->connect($server['server'], $port, $ttl);
            }

            // auth - just password
            if (isset($options['password'])) {
                $redis->auth($options['password']);
            }

            $this->redis = $redis;
        } else {
            $redisArrayOptions = array();
            foreach ($this->redisArrayOptionNames as $optionName) {
                if (array_key_exists($optionName, $options)) {
                    $redisArrayOptions[$optionName] = $options[$optionName];
                }
            }

            $serverArray = array();
            foreach ($servers as $server) {
                $serverString = $server['server'];
                if (isset($server['port'])) {
                    $serverString .= ':' . $server['port'];
                }

                $serverArray[] = $serverString;
            }

            $redis = new \RedisArray($serverArray, $redisArrayOptions);
        }

        // select database
        if (isset($options['database'])) {
            $redis->select($options['database']);
        }

        $this->redis = $redis;
    }

    /**
     * Properly close the connection.
     */
    public function close()
    {
        if ($this->redis instanceof \Redis) {
            try {
                $this->redis->close();
            } catch (\RedisException $e) {
                /*
                 * \Redis::close will throw a \RedisException("Redis server went away") exception if
                 * we haven't previously been able to connect to Redis or the connection has severed.
                 */
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $data)
    {
        return $this->redis->set($key, $data);
    }

    public function setex($key, $ttl, $data)
    {
        return $this->redis->setex($key, $ttl, $data);
    }

    public function incr($key)
    {
        return $this->redis->incr($key);
    }

    public function delete($key)
    {
        return $this->redis->delete($key);
    }

    public function flushDB()
    {
        return $this->redis->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return class_exists('Redis', false);
    }
}
