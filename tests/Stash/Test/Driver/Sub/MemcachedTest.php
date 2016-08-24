<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver\Sub;

use Stash\Test\Stubs\MemcachedStub;

class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruction()
    {
        $options = array(
            'servers' => array(
                '127.0.0.1:1121',
                '127.0.0.2:1121',
            ),

            'HASH' => 'MD5',
            'distribution' => 'CONSISTENT',
            'SERIALIZER' => 'PHP',
            'SEND_TIMEOUT' => 100,
            'PREFIX_KEY' => 'prefix',
            'SOCKET_SEND_SIZE' => 123,
            'SOCKET_RECV_SIZE' => 123,
            'CONNECT_TIMEOUT' => 123,
            'RETRY_TIMEOUT' => 123,
            'RECV_TIMEOUT' => 123,
            'POLL_TIMEOUT' => 123,
            'SERVER_FAILURE_LIMIT' => 123,
            'COMPRESSION' => true,
            'LIBKETAMA_COMPATIBLE' => true,
            'BUFFER_WRITES' => true,
            'BINARY_PROTOCOL' => true,
            'NO_BLOCK' => true,
            'TCP_NODELAY' => true,
            'CACHE_LOOKUPS' => true,
        );

        $servers = array(
            array('127.0.0.1', 1121, null),
            array('127.0.0.2', 1121, null),
        );

        $subMemcached = $this->getMockBuilder('Stash\Driver\Sub\Memcached')
                             ->setMethods(array('newMemcachedInstance'))
                             ->disableOriginalConstructor()
                             ->getMock();

        $memcachedStub = new MemcachedStub(true, true);
        $subMemcached->expects($this->once())
                     ->method('newMemcachedInstance')
                     ->willReturn($memcachedStub);


        $reflection = new \ReflectionClass('Stash\Driver\Sub\Memcached');
        $constructor = $reflection->getConstructor();
        $constructor->invoke($subMemcached, $servers, $options);

        $this->assertEquals(
            array(
                \Memcached::OPT_HASH => \Memcached::HASH_MD5,
                \Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
                \Memcached::OPT_SERIALIZER => \Memcached::SERIALIZER_PHP,
                \Memcached::OPT_SEND_TIMEOUT => 100,
                \Memcached::OPT_PREFIX_KEY => 'prefix',
                \Memcached::OPT_SOCKET_SEND_SIZE => 123,
                \Memcached::OPT_SOCKET_RECV_SIZE => 123,
                \Memcached::OPT_CONNECT_TIMEOUT => 123,
                \Memcached::OPT_RETRY_TIMEOUT => 123,
                \Memcached::OPT_RECV_TIMEOUT => 123,
                \Memcached::OPT_POLL_TIMEOUT => 123,
                \Memcached::OPT_SERVER_FAILURE_LIMIT => 123,
                \Memcached::OPT_COMPRESSION => true,
                \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
                \Memcached::OPT_BUFFER_WRITES => true,
                \Memcached::OPT_BINARY_PROTOCOL => true,
                \Memcached::OPT_NO_BLOCK => true,
                \Memcached::OPT_TCP_NODELAY => true,
                \Memcached::OPT_CACHE_LOOKUPS =>true,
            ),
            $memcachedStub->getOptions()
        );

        $this->assertEquals(
            array(
                array('127.0.0.1', 1121, null),
                array('127.0.0.2', 1121, null),
            ),
            $memcachedStub->getServerList()
        );
    }
}
