<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RedisArrayTest extends RedisTest
{
    protected $driverClass = 'Stash\Driver\Redis';

    protected $redisSecondServer = '127.0.0.1';
    protected $redisSecondPort = '6380';

    protected function setUp()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('RedisArray currently not supported by HHVM.');
        }

        parent::setUp();

        if (!($sock = @fsockopen($this->redisServer, $this->redisPort, $errno, $errstr, 1))) {
            $this->markTestSkipped('Redis server unavailable for testing.');
        }
        fclose($sock);

        if (!($sock = @fsockopen($this->redisSecondServer, $this->redisSecondPort, $errno, $errstr, 1))) {
            $this->markTestSkipped('Second Redis Server needed for more tests.');
        }
        fclose($sock);
    }

    protected function getOptions()
    {
        return array(
            'servers' => array(
                array('server' => $this->redisServer, 'port' => $this->redisPort, 'ttl' => 0.1),
                array('server' => $this->redisSecondServer, 'port' => $this->redisSecondPort, 'ttl' => 0.1),
            ),
        );
    }

    /**
     * @test
     */
    public function itShouldConstructARedisArray()
    {
        $driver = $this->getFreshDriver();
        $class = new \ReflectionClass($driver);
        $redisProperty = $class->getProperty('redis');
        $redisProperty->setAccessible(true);
        $redisArray = $redisProperty->getValue($driver);

        $this->assertInstanceOf('\RedisArray', $redisArray);
    }

    /**
     * @test
     */
    public function itShouldPassOptionsToRedisArray()
    {
        $redisArrayOptions = array(
            "previous"        => "something",
            "function"        => function ($key) { return $key; },
            "distributor"     => function ($key) { return 0; },
            "index"           => "something",
            "autorehash"      => "something",
            "pconnect"        => "something",
            "retry_interval"  => "something",
            "lazy_connect"    => "something",
            "connect_timeout" => "something",
        );

        $driverOptions = array_merge(
            $this->getOptions(),
            $redisArrayOptions
        );

        if (!extension_loaded('uopz')) {
            $this->markTestSkipped('uopz extension is necessarry in order to stub "new".');
        }

        uopz_backup('\RedisArray', '__construct');

        $self = $this;
        uopz_function(
            '\RedisArray',
            '__construct',
            function ($serverArray, $actualRedisArrayOptions) use ($self, $redisArrayOptions) {
                $self->assertEquals(
                    $redisArrayOptions,
                    $actualRedisArrayOptions
                );
            }
        );

        $this->getFreshDriver($driverOptions);

        uopz_restore('\RedisArray', '__construct');
    }
}
