<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test;

use Stash\Test\Stubs\DriverExceptionStub;
use Stash\Test\Stubs\PoolGetDriverStub;
use Stash\Pool;
use Stash\Item;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class CacheExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new DriverExceptionStub());
        $item->setPool($poolStub);
        $item->setKey(array('path', 'to', 'store'));

        $this->assertFalse($item->isDisabled());
        $this->assertFalse($item->set(array(1, 2, 3), 3600));
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testGet()
    {
        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new DriverExceptionStub());
        $item->setPool($poolStub);
        $item->setKey(array('path', 'to', 'get'));

        $this->assertFalse($item->isDisabled());
        $this->assertNull($item->get());
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testClear()
    {
        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new DriverExceptionStub());
        $item->setPool($poolStub);
        $item->setKey(array('path', 'to', 'clear'));

        $this->assertFalse($item->isDisabled());
        $this->assertFalse($item->clear());
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testPurge()
    {
        $pool = new Pool();
        $pool->setDriver(new DriverExceptionStub());

        $item = $pool->getItem('test');
        $this->assertFalse($item->isDisabled());
        $this->assertFalse($pool->purge());

        $item = $pool->getItem('test');
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
        $this->assertFalse($pool->purge());
    }

    public function testFlush()
    {
        $pool = new Pool();
        $pool->setDriver(new DriverExceptionStub());

        $item = $pool->getItem('test');
        $this->assertFalse($item->isDisabled());
        $this->assertFalse($pool->flush());

        $item = $pool->getItem('test');
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
        $this->assertFalse($pool->flush());
    }
}
