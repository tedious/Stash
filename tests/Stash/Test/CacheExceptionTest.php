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
        $item->setDriver(new DriverExceptionStub());
        $item->setKey(array('path', 'to', 'store'));

        $this->assertFalse($item->isDisabled());
        $this->assertFalse($item->set(array(1, 2, 3), 3600));
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testGet()
    {
        $item = new Item();
        $item->setDriver(new DriverExceptionStub());
        $item->setKey(array('path', 'to', 'get'));

        $this->assertFalse($item->isDisabled());
        $this->assertNull($item->get());
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testClear()
    {
        $item = new Item();
        $item->setDriver(new DriverExceptionStub());
        $item->setKey(array('path', 'to', 'clear'));

        $this->assertFalse($item->isDisabled());
        $this->assertFalse($item->clear());
        $this->assertTrue($item->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testPurge()
    {
        $pool = new Pool(new DriverExceptionStub());
        $stash = $pool->getItem('test');
        $this->assertFalse($stash->isDisabled());
        $this->assertFalse($pool->purge());

        $stash = $pool->getItem('test');
        $this->assertTrue($stash->isDisabled(), 'Is disabled after exception is thrown in driver');
        $this->assertFalse($pool->purge());
    }

    public function testFlush()
    {
        $pool = new Pool(new DriverExceptionStub());
        $stash = $pool->getItem('test');
        $this->assertFalse($stash->isDisabled());
        $this->assertFalse($pool->flush());

        $stash = $pool->getItem('test');
        $this->assertTrue($stash->isDisabled(), 'Is disabled after exception is thrown in driver');
        $this->assertFalse($pool->flush());
    }

}
