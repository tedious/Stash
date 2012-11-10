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
use Stash\Driver\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class PoolTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array(array('test', 'test'));
    protected $multiData = array('key' => 'value',
                                 'key1' => 'value1',
                                 'key2' => 'value2',
                                 'key3' => 'value3');

    public function testSetDriver()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('test');
        $this->assertAttributeInstanceOf('Stash\Driver\Ephemeral', 'driver', $stash, 'set driver is pushed to new stash objects');
    }

    public function testGetItem()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('base', 'one');
        $this->assertInstanceOf('Stash\Item', $stash, 'getItem returns a Stash\Item object');

        $stash->set($this->data);
        $storedData = $stash->get();
        $this->assertEquals($this->data, $storedData, 'getItem returns working Stash\Item object');
    }

    public function testGetItemIterator()
    {
        $pool = $this->getTestPool();

        $keys = array_keys($this->multiData);

        $cacheIterator = $pool->getItemIterator($keys);
        $keyData = $this->multiData;
        foreach($cacheIterator as $stash)
        {
            $key = $stash->getKey();
            $this->assertTrue($stash->isMiss(), 'new Cache in iterator is empty');
            $stash->set($keyData[$key]);
            unset($keyData[$key]);
        }
        $this->assertCount(0, $keyData, 'all keys are accounted for the in cache iterator');

        $cacheIterator = $pool->getItemIterator($keys);
        foreach($cacheIterator as $stash)
        {
            $key = $stash->getKey();
            $data = $stash->get($key);
            $this->assertEquals($this->multiData[$key], $data, 'data put into the pool comes back the same through iterators.');
        }
    }

    public function testClearCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('base', 'one');
        $stash->set($this->data);
        $this->assertTrue($pool->flush(), 'clear returns true');

        $stash = $pool->getItem('base', 'one');
        $this->assertNull($stash->get(), 'clear removes item');
        $this->assertTrue($stash->isMiss(), 'clear causes cache miss');
    }

    public function testPurgeCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('base', 'one');
        $stash->set($this->data, -600);
        $this->assertTrue($pool->purge(), 'purge returns true');

        $stash = $pool->getItem('base', 'one');
        $this->assertNull($stash->get(), 'purge removes item');
        $this->assertTrue($stash->isMiss(), 'purge causes cache miss');
    }


    public function testgetItemArrayConversion()
    {
        $pool = $this->getTestPool();

        $cache = $pool->getItem(array('base', 'one'));
        $this->assertEquals($cache->getKey(), 'base/one');
    }

    protected function getTestPool()
    {
        $driver = new Ephemeral(array());
        $pool = new Pool();
        $pool->setDriver($driver);
        return $pool;
    }
}
