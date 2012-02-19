<?php

namespace Stash\Test;

use Stash\Test\Handler\ExceptionTest;
use Stash\Cache;

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
