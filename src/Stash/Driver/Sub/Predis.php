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

use Predis\Connection\AggregateConnectionInterface;

class Predis
{
    protected $predis;

    public function __construct(array $servers, array $options = array())
    {
        $predisServers = array();

        foreach ($servers as $server) {
            if (isset($server['socket']) && $server['socket']) {
                $s = array(
                    'scheme' => 'unix',
                    'path'   => $server['socket'],
                );
            } else {
                $s = array(
                    'scheme'  => 'tcp',
                    'host'    => $server['server'],
                    'port'    => isset($server['port']) ? $server['port'] : 6379,
                    'timeout' => isset($server['ttl']) ? $server['ttl'] : 0.1,
                );
            }

            // auth - just password
            if (isset($options['password'])) {
                $s['password'] = $options['password'];
            }

            if (isset($options['database'])) {
                $s['database'] = $options['database'];
            }

            $predisServers[] = $s;
        }

        if (count($predisServers) == 1) {
            $predisServers = $predisServers[0];
        }

        $this->predis = new \Predis\Client($predisServers);
    }

    /**
     * Properly close the connection.
     */
    public function close()
    {
        $this->predis->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->predis->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $data)
    {
        return $this->predis->set($key, $data);
    }

    public function setex($key, $ttl, $data)
    {
        return $this->predis->setex($key, $ttl, $data) == 'OK';
    }

    public function incr($key)
    {
        return $this->predis->incr($key);
    }

    public function delete($key)
    {
        return $this->predis->del($key);
    }

    public function flushDB()
    {
        if ($this->predis->getConnection() instanceof AggregateConnectionInterface) {
            foreach ($this->predis as $conn) {
                $conn->flushdb();
            }
            return true;
        } else {
            return $this->predis->flushdb();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return class_exists('Predis\\Client');
    }
}
