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
 * @internal
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class Memcached
{
    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Constructs the Memcached subdriver.
     *
     * Takes an array of servers, with array containing another array with the server, port and weight.
     * array(array( '127.0.0.1', 11211, 20), array( '192.168.10.12', 11213, 80), array( '192.168.10.12', 11211, 80));
     *
     * Takes an array of options which map to the "\Memcached::OPT_" settings (\Memcached::OPT_COMPRESSION => "compression").
     *
     * @param  array                             $servers
     * @param  array                             $options
     * @throws \Stash\Exception\RuntimeException
     */
    public function __construct($servers = array(), $options = array())
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
                            'SERVER_FAILURE_LIMIT',
                            'CLIENT_MODE',
                            'REMOVE_FAILED_SERVERS',
        );

        $memcached = new \Memcached();

        $serverList = $memcached->getServerList();
        if (empty($serverList)) {
            $memcached->addServers($servers);
        }

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
                case 'REMOVE_FAILED_SERVERS':
                    if (!is_bool($value)) {
                        throw new RuntimeException('Memcached option ' . $name . ' requires boolean value');
                    }
                    break;
            }

            if (!@$memcached->setOption(constant('\Memcached::OPT_' . $name), $value)) {
                throw new RuntimeException('Memcached option Memcached::OPT_' . $name . ' not accepted by memcached extension.');
            }
        }

        $this->memcached = $memcached;
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

        return $this->memcached->set($key, array('data' => $value, 'expiration' => $expire), $expire);
    }

    /**
     * Retrieves the data from memcached.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->memcached->get($key);
        if ($value === false && $this->memcached->getResultCode() == \Memcached::RES_NOTFOUND) {
            return false;
        }

        return $value;
    }

    /**
     * This function emulates runs the cas memcache functionlity.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    public function cas($key, $value)
    {
        $token = null;
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
     * Returns true if the Memcached extension is installed.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        return class_exists('Memcached', false);
    }
}
