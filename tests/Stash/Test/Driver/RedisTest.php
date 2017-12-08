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
class RedisTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Redis';
    protected $redisServer = '127.0.0.1';
    protected $redisPort = '6379';

    protected $redisNoServer = '127.0.0.1';
    protected $redisNoPort = '6381';
    protected $persistence = true;

    protected function setUp()
    {
        if (!$this->setup) {
            $this->startTime = time();
            $this->expiration = $this->startTime + 3600;

            if (!($sock = @fsockopen($this->redisServer, $this->redisPort, $errno, $errstr, 1))) {
                $this->markTestSkipped('Redis server unavailable for testing.');
            }
            fclose($sock);

            if ($sock = @fsockopen($this->redisNoServer, $this->redisNoPort, $errno, $errstr, 1)) {
                fclose($sock);
                $this->markTestSkipped("No server should be listening on {$this->redisNoServer}:{$this->redisNoPort} " .
                                       "so that we can test for exceptions.");
            }

            if (!$this->getFreshDriver()) {
                $this->markTestSkipped('Driver class unsuited for current environment');
            }

            $this->data['object'] = new \stdClass();
            $this->data['large_string'] = str_repeat('apples', ceil(200000 / 6));
        }
    }

    protected function getOptions()
    {
        return array('servers' => array(
            array('server' => $this->redisServer, 'port' => $this->redisPort, 'ttl' => 0.1)
        ));
    }

    protected function getInvalidOptions()
    {
        return array('servers' => array(
            array('server' => $this->redisNoServer, 'port' => $this->redisNoPort, 'ttl' => 0.1)
        ));
    }

    /**
     * @expectedException \PHPUnit\Framework\Error\Warning
     */
    public function testBadDisconnect()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This test can not run on HHVM as HHVM throws a different set of errors.');
        }

        $driver = $this->getFreshDriver($this->getInvalidOptions());
        $driver->__destruct();
        $driver = null;
    }
}
