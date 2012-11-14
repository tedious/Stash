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

use Stash\Test\Exception\ExceptionTest;
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
        $driver = new ExceptionTest();
        $stash = new Item($driver, array('path', 'to', 'store'));
        $this->assertFalse($stash->isDisabled());
        $this->assertFalse($stash->set(array(1, 2, 3), 3600));
        $this->assertTrue($stash->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testGet()
    {
        $stash = new Item(new ExceptionTest(), array('path', 'to', 'get'));
        $this->assertFalse($stash->isDisabled());
        $this->assertNull($stash->get());
        $this->assertTrue($stash->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testClear()
    {
        $stash = new Item(new ExceptionTest(), array('path', 'to', 'clear'));
        $this->assertFalse($stash->isDisabled());
        $this->assertFalse($stash->clear());
        $this->assertTrue($stash->isDisabled(), 'Is disabled after exception is thrown in driver');
    }

    public function testPurge()
    {
        $pool = new Pool(new ExceptionTest());
        $stash = $pool->getItem('test');
        $this->assertFalse($stash->isDisabled());
        $this->assertFalse($pool->purge());

        $stash = $pool->getItem('test');
        $this->assertTrue($stash->isDisabled(), 'Is disabled after exception is thrown in driver');
    }
}
