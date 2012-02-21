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
use Stash\Cache;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class CacheExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testStore()
    {
        $handler = new ExceptionTest();
        $stash = new Cache($handler);
        $stash->setupKey('path', 'to', 'store');
        $this->assertFalse($stash->store(array(1, 2, 3), 3600));
    }

    public function testGet()
    {
        $stash = new Cache(new ExceptionTest());
        $stash->setupKey('path', 'to', 'get');
        $this->assertNull($stash->get());
    }

    public function testClear()
    {
        $stash = new Cache(new ExceptionTest());
        $stash->setupKey('path', 'to', 'clear');
        $this->assertFalse($stash->clear());
    }

    public function testPurge()
    {
        $stash = new Cache(new ExceptionTest());
        $this->assertFalse($stash->purge());
    }
}
