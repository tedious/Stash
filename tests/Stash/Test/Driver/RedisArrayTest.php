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
        parent::setUp();

        if(!($sock = @fsockopen($this->redisServer, $this->redisPort, $errno, $errstr, 1))) {
            $this->markTestSkipped('Redis server unavailable for testing.');
        }
        fclose($sock);

        if(!($sock = @fsockopen($this->redisSecondServer, $this->redisSecondPort, $errno, $errstr, 1))) {
            $this->markTestSkipped('Second Redis Server needed for more tests.');
        }
        fclose($sock);
    }

    protected function getOptions()
    {
        return array(
            array('server' => $this->redisServer, 'port' => $this->redisPort, 'ttl' => 0.1),
            array('server' => $this->redisSecondServer, 'port' => $this->redisSecondPort, 'ttl' => 0.1),
        );
    }


}
