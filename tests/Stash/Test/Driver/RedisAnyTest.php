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

use Stash\Test\Stubs\PoolGetDriverStub;
use Stash\Driver\Redis;
use Stash\Item;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RedisAnyTest extends \PHPUnit_Framework_TestCase
{
    protected $driverClass = 'Stash\Driver\Redis';

    protected $redisServer = '127.0.0.1';
    protected $redisPort = '6379';

    protected function setUp()
    {
        $driverClass = $this->driverClass;

        if (!$driverClass::isAvailable()) {
            $this->markTestSkipped('Driver class unsuited for current environment');

            return;
        }

        if (!($sock = @fsockopen($this->redisServer, $this->redisPort, $errno, $errstr, 1))) {
            $this->markTestSkipped('Redis tests require redis server');

            return;
        }

        fclose($sock);
    }

    public function testConstruction()
    {
        $key = array('apple', 'sauce');

        $options = array(
            'servers' => array(
                array('server' => $this->redisServer, 'port' => $this->redisPort, 'ttl' => 0.1)
            ),
        );
        $driver = new Redis($options);

        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);
        $item->setPool($poolStub);

        $item->setKey($key);
        $this->assertTrue($item->set($key)->save(), 'Able to load and store with unconfigured extension.');
    }
}
