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

use Stash\Pool;
use Stash\Handler\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class PoolTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array(array('test', 'test'));

    public function testSetHandler()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getCache('test');
        $this->assertAttributeInstanceOf('Stash\Handler\Ephemeral', 'handler', $stash, 'set handler is pushed to new stash objects');
    }

    public function testGetCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getCache('base', 'one');
        $this->assertInstanceOf('Stash\Cache', $stash, 'getCache returns a Stash\Cache object');

        $stash->store($this->data);
        $storedData = $stash->get();
        $this->assertEquals($this->data, $storedData, 'getCache returns working Stash\Cache object');
    }

    public function testClearCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getCache('base', 'one');
        $stash->store($this->data);
        $this->assertTrue($pool->flush(), 'clear returns true');

        $stash = $pool->getCache('base', 'one');
        $this->assertNull($stash->get(), 'clear removes item');
        $this->assertTrue($stash->isMiss(), 'clear causes cache miss');
    }

    public function testPurgeCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getCache('base', 'one');
        $stash->store($this->data);
        $this->assertTrue($pool->purge(), 'purge returns true');

        $stash = $pool->getCache('base', 'one');
        $this->assertNull($stash->get(), 'purge removes item');
        $this->assertTrue($stash->isMiss(), 'purge causes cache miss');
    }

    protected function getTestPool()
    {
        $handler = new Ephemeral(array());
        $pool = new Pool();
        $pool->setHandler($handler);
        return $pool;
    }
}
