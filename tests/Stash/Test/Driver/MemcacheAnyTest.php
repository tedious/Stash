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
use Stash\Driver\Memcache;
use Stash\Item;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class MemcacheAnyTest extends \PHPUnit\Framework\TestCase
{
    protected $driverClass = 'Stash\Driver\Memcache';

    protected $servers = array('127.0.0.1', '11211');

    protected function setUp()
    {
        $driverClass = $this->driverClass;

        if (!$driverClass::isAvailable()) {
            $this->markTestSkipped('Driver class unsuited for current environment');

            return;
        }

        if (!($sock = @fsockopen($this->servers[0], $this->servers[1], $errno, $errstr, 1))) {
            $this->markTestSkipped('Memcache tests require memcache server');

            return;
        }

        fclose($sock);
    }

    public function testConstruction()
    {
        $key = array('apple', 'sauce');

        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $driver = new Memcache($options);

        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);
        $item->setPool($poolStub);

        $item->setKey($key);
        $this->assertTrue($item->set($key)->save(), 'Able to load and store with unconfigured extension.');
    }
}
