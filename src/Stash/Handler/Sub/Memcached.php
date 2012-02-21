<?php
/**
 * Stash
 *
 * Copyright (c) 2009-2011, Robert Hafner <tedivm@tedivm.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Robert Hafner nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stash
 * @subpackage Handlers
 * @author     Robert Hafner <tedivm@tedivm.com>
 * @copyright  2009-2011 Robert Hafner <tedivm@tedivm.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://code.google.com/p/stash/
 * @since      File available since Release 0.9.1
 * @version    Release: 0.9.5
 */

namespace Stash\Handler\Sub;

use Stash\Exception\MemcacheException;
use Stash\Handler\EnabledInterface;

class Memcached implements EnabledInterface
{
    /**
     * @var Memcached
     */
    protected $memcached;

    public function initialize($servers, $options = array())
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
                        throw new MemcacheException('Memcached option ' . $name . ' requires valid memcache hash option value');
                    }
                    $value = constant('\Memcached::HASH_' . $value);
                    break;

                case 'DISTRIBUTION':
                    $value = strtoupper($value);
                    if (!defined('\Memcached::DISTRIBUTION_' . $value)) {
                        throw new MemcacheException('Memcached option ' . $name . ' requires valid memcache distribution option value');
                    }
                    $value = constant('\Memcached::DISTRIBUTION_' . $value);
                    break;

                case 'SERIALIZER':
                    $value = strtoupper($value);
                    if (!defined('\Memcached::SERIALIZER_' . $value)) {
                        throw new MemcacheException('Memcached option ' . $name . ' requires valid memcache serializer option value');
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
                        throw new MemcacheException('Memcached option ' . $name . ' requires numeric value');
                    }
                    break;

                case 'PREFIX_KEY':
                    if (!is_string($value)) {
                        throw new MemcacheException('Memcached option ' . $name . ' requires string value');
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
                        throw new MemcacheException('Memcached option ' . $name . ' requires boolean value');
                    }
                    break;
            }

            $memcached->setOption(constant('\Memcached::OPT_' . $name), $value);
        }

        $this->memcached = $memcached;
    }

    public function set($key, $value, $expire = null)
    {
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

    public function canEnable()
    {
        return class_exists('Memcached', false);
    }
}
