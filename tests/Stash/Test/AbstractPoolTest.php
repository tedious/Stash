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

use Stash\Exception\InvalidArgumentException;
use Stash\Pool;
use Stash\Driver\Ephemeral;
use Stash\Test\Stubs\LoggerStub;
use Stash\Test\Stubs\DriverExceptionStub;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class AbstractPoolTest extends \PHPUnit\Framework\TestCase
{
    protected $data = array(array('test', 'test'));
    protected $multiData = array('key' => 'value',
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3');

    protected $poolClass = '\Stash\Pool';

    public function testSetDriver()
    {
        $driver = new Ephemeral();
        $pool = new $this->poolClass($driver);
        $this->assertAttributeEquals($driver, 'driver', $pool);
    }

    public function testSetItemDriver()
    {
        $pool = $this->getTestPool();
        $stash = $pool->getItem('test');
        $this->assertAttributeInstanceOf('Stash\Driver\Ephemeral', 'driver', $stash, 'set driver is pushed to new stash objects');
    }

    public function testSetItemClass()
    {
        $mockItem = $this->createMock('Stash\Interfaces\ItemInterface');
        $mockClassName = get_class($mockItem);
        $pool = $this->getTestPool();

        $this->assertTrue($pool->setItemClass($mockClassName));
        $this->assertAttributeEquals($mockClassName, 'itemClass', $pool);
    }

    public function testSetItemClassFakeClassException()
    {
        try {
            $pool = $this->getTestPool();
            $pool->setItemClass('FakeClassName');
        } catch (\Exception $expected) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testSetItemClassImproperClassException()
    {
        try {
            $pool = $this->getTestPool();
            $pool->setItemClass('\stdClass');
        } catch (\Exception $expected) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    public function testGetItem()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('base/one');
        $this->assertInstanceOf('Stash\Item', $stash, 'getItem returns a Stash\Item object');

        $stash->set($this->data)->save();
        $storedData = $stash->get();
        $this->assertEquals($this->data, $storedData, 'getItem returns working Stash\Item object');

        $key = $stash->getKey();
        $this->assertEquals('base/one', $key, 'Pool sets proper Item key.');

        $pool->setNamespace('TestNamespace');
        $item = $pool->getItem('test/item');

        $this->assertAttributeEquals('TestNamespace', 'namespace', $item, 'Pool sets Item namespace.');
    }

    public function testSaveItem()
    {
        $pool = $this->getTestPool();

        $this->assertFalse($pool->hasItem('base/one'), 'Pool->hasItem() returns false for item without stored data.');
        $item = $pool->getItem('base/one');
        $this->assertInstanceOf('Stash\Item', $item, 'getItem returns a Stash\Item object');

        $key = $item->getKey();
        $this->assertEquals('base/one', $key, 'Pool sets proper Item key.');

        $item->set($this->data);
        $this->assertTrue($pool->save($item), 'Pool->save() returns true.');
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData, 'Pool->save() returns proper data on passed Item.');

        $item = $pool->getItem('base/one');
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData, 'Pool->save() returns proper data on new Item instance.');

        $this->assertTrue($pool->hasItem('base/one'), 'Pool->hasItem() returns true for item with stored data.');

        $pool->setNamespace('TestNamespace');
        $item = $pool->getItem('test/item');

        $this->assertAttributeEquals('TestNamespace', 'namespace', $item, 'Pool sets Item namespace.');
    }


    public function testSaveDeferredItem()
    {
        $pool = $this->getTestPool();

        $this->assertFalse($pool->hasItem('base/one'), 'Pool->hasItem() returns false for item without stored data.');
        $item = $pool->getItem('base/one');
        $this->assertInstanceOf('Stash\Item', $item, 'getItem returns a Stash\Item object');

        $key = $item->getKey();
        $this->assertEquals('base/one', $key, 'Pool sets proper Item key.');

        $item->set($this->data);
        $this->assertTrue($pool->saveDeferred($item), 'Pool->save() returns true.');
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData, 'Pool->save() returns proper data on passed Item.');

        $item = $pool->getItem('base/one');
        $storedData = $item->get();
        $this->assertEquals($this->data, $storedData, 'Pool->save() returns proper data on new Item instance.');

        $this->assertTrue($pool->hasItem('base/one'), 'Pool->hasItem() returns true for item with stored data.');

        $pool->setNamespace('TestNamespace');
        $item = $pool->getItem('test/item');

        $this->assertAttributeEquals('TestNamespace', 'namespace', $item, 'Pool sets Item namespace.');
    }

    public function testHasItem()
    {
        $pool = $this->getTestPool();
        $this->assertFalse($pool->hasItem('base/one'), 'Pool->hasItem() returns false for item without stored data.');
        $item = $pool->getItem('base/one');
        $item->set($this->data);
        $pool->save($item);
        $this->assertTrue($pool->hasItem('base/one'), 'Pool->hasItem() returns true for item with stored data.');
    }

    public function testCommit()
    {
        $pool = $this->getTestPool();
        $this->assertTrue($pool->commit());
    }


    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid or Empty Node passed to getItem constructor.
     */
    public function testGetItemInvalidKeyMissingNode()
    {
        $pool = $this->getTestPool();
        $item = $pool->getItem('This/Test//Fail');
    }

    public function testGetItems()
    {
        $pool = $this->getTestPool();

        $keys = array_keys($this->multiData);

        $cacheIterator = $pool->getItems($keys);
        $keyData = $this->multiData;
        foreach ($cacheIterator as $key => $stash) {
            $this->assertTrue($stash->isMiss(), 'new Cache in iterator is empty');
            $stash->set($keyData[$key])->save();
            unset($keyData[$key]);
        }
        $this->assertCount(0, $keyData, 'all keys are accounted for the in cache iterator');

        $cacheIterator = $pool->getItems($keys);
        foreach ($cacheIterator as $key => $stash) {
            $this->assertEquals($key, $stash->getKey(), 'Item key is not equals key in iterator');
            $data = $stash->get($key);
            $this->assertEquals($this->multiData[$key], $data, 'data put into the pool comes back the same through iterators.');
        }
    }

    public function testDeleteItems()
    {
        $pool = $this->getTestPool();

        $keys = array_keys($this->multiData);

        $cacheIterator = $pool->getItems($keys);
        $keyData = $this->multiData;
        foreach ($cacheIterator as $stash) {
            $key = $stash->getKey();
            $this->assertTrue($stash->isMiss(), 'new Cache in iterator is empty');
            $stash->set($keyData[$key])->save();
            unset($keyData[$key]);
        }
        $this->assertCount(0, $keyData, 'all keys are accounted for the in cache iterator');

        $cacheIterator = $pool->getItems($keys);
        foreach ($cacheIterator as $item) {
            $key = $item->getKey();
            $data = $item->get($key);
            $this->assertEquals($this->multiData[$key], $data, 'data put into the pool comes back the same through iterators.');
        }

        $this->assertTrue($pool->deleteItems($keys), 'deleteItems returns true.');
        $cacheIterator = $pool->getItems($keys);
        foreach ($cacheIterator as $item) {
            $this->assertTrue($item->isMiss(), 'data cleared using deleteItems is removed from the cache.');
        }
    }



    public function testClearCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('base/one');
        $stash->set($this->data)->save();
        $this->assertTrue($pool->clear(), 'clear returns true');

        $stash = $pool->getItem('base/one');
        $this->assertNull($stash->get(), 'clear removes item');
        $this->assertTrue($stash->isMiss(), 'clear causes cache miss');
    }

    public function testPurgeCache()
    {
        $pool = $this->getTestPool();

        $stash = $pool->getItem('base/one');
        $stash->set($this->data)->expiresAfter(-600)->save();
        $this->assertTrue($pool->purge(), 'purge returns true');

        $stash = $pool->getItem('base/one');
        $this->assertNull($stash->get(), 'purge removes item');
        $this->assertTrue($stash->isMiss(), 'purge causes cache miss');
    }

    public function testNamespacing()
    {
        $pool = $this->getTestPool();

        $this->assertAttributeEquals(null, 'namespace', $pool, 'Namespace starts empty.');
        $this->assertTrue($pool->setNamespace('TestSpace'), 'setNamespace returns true.');
        $this->assertAttributeEquals('TestSpace', 'namespace', $pool, 'setNamespace sets the namespace.');
        $this->assertEquals('TestSpace', $pool->getNamespace(), 'getNamespace returns current namespace.');

        $this->assertTrue($pool->setNamespace(), 'setNamespace returns true when setting null.');
        $this->assertAttributeEquals(null, 'namespace', $pool, 'setNamespace() empties namespace.');
        $this->assertFalse($pool->getNamespace(), 'getNamespace returns false when no namespace is set.');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Namespace must be alphanumeric.
     */
    public function testInvalidNamespace()
    {
        $pool = $this->getTestPool();
        $pool->setNamespace('!@#$%^&*(');
    }


    public function testSetLogger()
    {
        $pool = $this->getTestPool();

        $driver = new DriverExceptionStub();
        $pool->setDriver($driver);

        $logger = new LoggerStub();
        $pool->setLogger($logger);

        $this->assertAttributeInstanceOf('Stash\Test\Stubs\LoggerStub', 'logger', $pool, 'setLogger injects logger into Pool.');

        $item = $pool->getItem('testItem');
        $this->assertAttributeInstanceOf('Stash\Test\Stubs\LoggerStub', 'logger', $item, 'setLogger injects logger into Pool.');
    }

    public function testLoggerClear()
    {
        $pool = $this->getTestPool();

        $driver = new DriverExceptionStub();
        $pool->setDriver($driver);

        $logger = new LoggerStub();
        $pool->setLogger($logger);

        // triggerlogging
        $pool->clear();

        $this->assertInstanceOf(
            'Stash\Test\Exception\TestException',
            $logger->lastContext['exception'],
            'Logger was passed exception in event context.'
        );

        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "get" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }

    public function testLoggerPurge()
    {
        $pool = $this->getTestPool();

        $driver = new DriverExceptionStub();
        $pool->setDriver($driver);

        $logger = new LoggerStub();
        $pool->setLogger($logger);

        // triggerlogging
        $pool->purge();

        $this->assertInstanceOf(
            'Stash\Test\Exception\TestException',
            $logger->lastContext['exception'],
            'Logger was passed exception in event context.'
        );
        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "set" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }

    /**
     * @return \Stash\Pool
     */
    protected function getTestPool()
    {
        return new $this->poolClass();
    }
}
