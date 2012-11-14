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

use Stash\Exception\MemcacheException;
use Stash\Exception\RuntimeException;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Memcached
{
    /**
     * @var Memcached
     */
    protected $memcached;

    public function initialize($servers, array $options = array())
    {
        // build this array here instead of as a class variable since the constants are only defined if the extension
        // exists
        $memOptions = array('COMPRESSION',
                            'SERIALIZER',
                            'PREFIX_KEY',
                            'HASH',
                            'DISTRIBUTION',
                            'LIBKETAMA_COMPATIBLE',
                            'BUFFER_WRITES',
                            'BINARY_PROTOCOL',
                            'NO_BLOCK',
                            'TCP_NODELAY',
                            'SOCKET_SEND_SIZE',
                            'SOCKET_RECV_SIZE',
                            'CONNECT_TIMEOUT',
                            'RETRY_TIMEOUT',
                            'SEND_TIMEOUT',
                            'RECV_TIMEOUT',
                            'POLL_TIMEOUT',
                            'CACHE_LOOKUPS',
                            'SERVER_FAILURE_LIMIT'
        );

        $memcached = new \Memcached();

        $memcached->addServers($servers);

        foreach ($options as $name => $value) {
            $name = strtoupper($name);

            if (!in_array($name, $memOptions) || !defined('\Memcached::OPT_' . $name)) {
                continue;
            }

            switch ($name) {
                case 'HASH':
                    $value = strtoupper($value);
                    if (!defined('\Memcached::HASH_' . $value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires valid memcache hash option value');
                    }
                    $value = constant('\Memcached::HASH_' . $value);
                    break;

                case 'DISTRIBUTION':
                    $value = strtoupper($value);
                    if (!defined('\Memcached::DISTRIBUTION_' . $value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires valid memcache distribution option value');
                    }
                    $value = constant('\Memcached::DISTRIBUTION_' . $value);
                    break;

                case 'SERIALIZER':
                    $value = strtoupper($value);
                    if (!defined('\Memcached::SERIALIZER_' . $value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires valid memcache serializer option value');
                    }
                    $value = constant('\Memcached::SERIALIZER_' . $value);
                    break;

                case 'SOCKET_SEND_SIZE':
                case 'SOCKET_RECV_SIZE':
                case 'CONNECT_TIMEOUT':
                case 'RETRY_TIMEOUT':
                case 'SEND_TIMEOUT':
                case 'RECV_TIMEOUT':
                case 'POLL_TIMEOUT':
                case 'SERVER_FAILURE_LIMIT':
                    if (!is_numeric($value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires numeric value');
                    }
                    break;

                case 'PREFIX_KEY':
                    if (!is_string($value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires string value');
                    }
                    break;

                case 'COMPRESSION':
                case 'LIBKETAMA_COMPATIBLE':
                case 'BUFFER_WRITES':
                case 'BINARY_PROTOCOL':
                case 'NO_BLOCK':
                case 'TCP_NODELAY':
                case 'CACHE_LOOKUPS':
                    if (!is_bool($value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires boolean value');
                    }
                    break;
            }

            if(!@$memcached->setOption(constant('\Memcached::OPT_' . $name), $value)) {
                throw new RuntimeException('Memcached option Memcached::OPT_' . $name . ' not accepted by memcached extension.');
            }
        }

        $this->memcached = $memcached;
    }

    public function set($key, $value, $expire = null)
    {
        if(isset($expire) && $expire < time()) {
            return true;
        }
        return $this->memcached->set($key, array('data' => $value, 'expiration' => $expire), $expire);
    }

    public function get($key)
    {
        $value = $this->memcached->get($key);
        if ($value === false && $this->memcached->getResultCode() == \Memcached::RES_NOTFOUND) {
            return false;
        }

        return $value;
    }

    public function cas($key, $value)
    {
        if (($rValue = $this->memcached->get($key, null, $token)) !== false) {
            return $rValue;
        }

        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            $this->memcached->add($key, $value);
        } else {
            $this->memcached->cas($token, $key, $value);
        }
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
        return class_exists('Memcached', false);
    }
}
