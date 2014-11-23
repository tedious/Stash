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

use Stash\Item;
use Stash\Utilities;
use Stash\Driver\Ephemeral;

use Stash\Test\Stubs\PoolGetDriverStub;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @todo find out why this has to be abstract to work (see https://github.com/tedivm/Stash/pull/10)
 */
abstract class AbstractItemTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array('string' => 'Hello world!',
                            'complexString' => "\t\t\t\tHello\r\n\rWorld!",
                            'int' => 4234,
                            'negint' => -6534,
                            'float' => 1.8358023545,
                            'negfloat' => -5.7003249023,
                            'false' => false,
                            'true' => true,
                            'null' => null,
                            'array' => array(3, 5, 7),
                            'hashmap' => array('one' => 1, 'two' => 2),
                            'multidemensional array' => array(array(5345),
                                                              array(3, 'hello', false, array('one' => 1, 'two' => 2))
                            )
    );

    protected $expiration;
    protected $startTime;
    private $setup = false;
    protected $driver;

    protected $itemClass = '\Stash\Item';

    public static function tearDownAfterClass()
    {
        Utilities::deleteRecursive(Utilities::getBaseDirectory());
    }

    protected function setUp()
    {
        if (!$this->setup) {
            $this->startTime = time();
            $this->expiration = $this->startTime + 3600;
            $this->data['object'] = new \stdClass();
        }
    }

    /**
     * This just makes it slightly easier to extend AbstractCacheTest to
     * other Item types.
     *
     * @return \Stash\Interfaces\ItemInterface
     */
    protected function getItem()
    {
        return new $this->itemClass();
    }

    public function testConstruct($key = array())
    {
        if (!isset($this->driver)) {
            $this->driver = new Ephemeral(array());
        }

        $item = $this->getItem();
        $this->assertTrue(is_a($item, 'Stash\Item'), 'Test object is an instance of Stash');

        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($this->driver);
        $item->setPool($poolStub);

        $item->setKey($key);

        return $item;
    }

    public function testSetupKey()
    {
        $keyString = 'this/is/the/key';
        $keyArray = array('this', 'is', 'the', 'key');
        $keyNormalized = array('cache', 'this', 'is', 'the', 'key');

        $stashArray = $this->testConstruct($keyArray);
        $this->assertAttributeInternalType('string', 'keyString', $stashArray, 'Argument based keys setup keystring');
        $this->assertAttributeInternalType('array', 'key', $stashArray, 'Array based keys setup key');

        $returnedKey = $stashArray->getKey();
        $this->assertEquals($keyString, $returnedKey, 'getKey returns properly normalized key from array argument.');
    }

    public function testSet()
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct($key);
            $this->assertAttributeInternalType('string', 'keyString', $stash, 'Argument based keys setup keystring');
            $this->assertAttributeInternalType('array', 'key', $stash, 'Argument based keys setup key');

            $this->assertTrue($stash->set($value), 'Driver class able to store data type ' . $type);
        }

        $item = $this->getItem();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new Ephemeral(array()));
        $item->setPool($poolStub);
        $this->assertFalse($item->set($this->data), 'Item without key returns false for set.');
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct($key);
            $stash->set($value);

            // new object, but same backend
            $stash = $this->testConstruct($key);
            $data = $stash->get();
            $this->assertEquals($value, $data, 'getData ' . $type . ' returns same item as stored');
        }

        if (!isset($this->driver)) {
            $this->driver = new Ephemeral();
        }

        $item = $this->getItem();

        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new Ephemeral());
        $item->setPool($poolStub);

        $this->assertEquals(null, $item->get(), 'Item without key returns null for get.');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Item requires keys as arrays.
     */
    public function testGetItemInvalidKey()
    {
        $item = $this->getItem();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new Ephemeral(array()));
        $item->setPool($poolStub);
        $item->setKey('This is not an array');
    }

    public function testLock()
    {
        $item = $this->getItem();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new Ephemeral());
        $item->setPool($poolStub);
        $this->assertFalse($item->lock(), 'Item without key returns false for lock.');
    }

    public function testInvalidation()
    {
        $key = array('path', 'to', 'item');
        $oldValue = 'oldValue';
        $newValue = 'newValue';

        $runningStash = $this->testConstruct($key);
        $runningStash->set($oldValue, -300);

        // Test without stampede
        $controlStash = $this->testConstruct($key);

        $return = $controlStash->get(Item::SP_VALUE, $newValue);
        $this->assertEquals($oldValue, $return, 'Old value is returned');
        $this->assertTrue($controlStash->isMiss());
        unset($controlStash);

        // Enable stampede control
        $runningStash->lock();
        $this->assertAttributeEquals(true, 'stampedeRunning', $runningStash, 'Stampede flag is set.');

        // Old
        $oldStash = $this->testConstruct($key);

        $return = $oldStash->get(Item::SP_OLD);
        $this->assertEquals($oldValue, $return, 'Old value is returned');
        $this->assertFalse($oldStash->isMiss());
        unset($oldStash);

        // Value
        $valueStash = $this->testConstruct($key);

        $return = $valueStash->get(Item::SP_VALUE, $newValue);
        $this->assertEquals($newValue, $return, 'New value is returned');
        $this->assertFalse($valueStash->isMiss());
        unset($valueStash);

        // Sleep
        $sleepStash = $this->testConstruct($key);

        $start = microtime(true);
        $return = $sleepStash->get(array(Item::SP_SLEEP, 250, 2));
        $end = microtime(true);

        $this->assertTrue($sleepStash->isMiss());
        $sleepTime = ($end - $start) * 1000;

        $this->assertGreaterThan(500, $sleepTime, 'Sleep method sleeps for required time.');
        $this->assertLessThan(510, $sleepTime, 'Sleep method does not oversleep.');

        unset($sleepStash);

        // Unknown - if a random, unknown method is passed for invalidation we should rely on the default method
        $unknownStash = $this->testConstruct($key);

        $return = $unknownStash->get(78);
        $this->assertEquals($oldValue, $return, 'Old value is returned');
        $this->assertTrue($unknownStash->isMiss(), 'Cache is marked as miss');
        unset($unknownStash);

        // Test that storing the cache turns off stampede mode.
        $runningStash->set($newValue, 30);
        $this->assertAttributeEquals(false, 'stampedeRunning', $runningStash, 'Stampede flag is off.');
        unset($runningStash);

        // Precompute - test outside limit
        $precomputeStash = $this->testConstruct($key);

        $return = $precomputeStash->get(Item::SP_PRECOMPUTE, 10);
        $this->assertFalse($precomputeStash->isMiss(), 'Cache is marked as hit');
        unset($precomputeStash);

        // Precompute - test inside limit
        $precomputeStash = $this->testConstruct($key);

        $return = $precomputeStash->get(Item::SP_PRECOMPUTE, 35);
        $this->assertTrue($precomputeStash->isMiss(), 'Cache is marked as miss');
        unset($precomputeStash);

        // Test Stampede Flag Expiration
        $key = array('stampede', 'expire');
        $Item_SPtest = $this->testConstruct($key);
        $Item_SPtest->set($oldValue, -300);
        $Item_SPtest->lock(-5);
        $this->assertEquals($oldValue, $Item_SPtest->get(Item::SP_VALUE, $newValue), 'Expired lock is ignored');
    }

    public function testSetWithDateTime()
    {
        $expiration = new \DateTime('now');
        $expiration->add(new \DateInterval('P1D'));

        $key = array('base', 'expiration', 'test');
        $stash = $this->testConstruct($key);
        $stash->set(array(1, 2, 3, 'apples'), $expiration);

        $stash = $this->testConstruct($key);
        $data = $stash->get();
        $this->assertEquals(array(1, 2, 3, 'apples'), $data, 'getData returns data stores using a datetime expiration');
    }

    public function testGetCreation()
    {
        $creation = new \DateTime('now');
        $creation->add(new \DateInterval('PT10S')); // expire 10 seconds after createdOn
        $creationTS = $creation->getTimestamp();

        $key = array('getCreation', 'test');
        $stash = $this->testConstruct($key);

        $this->assertFalse($stash->getCreation(), 'no record exists yet, return null');

        $stash->set(array('stuff'), $creation);

        $stash = $this->testConstruct($key);
        $createdOn = $stash->getCreation();
        $this->assertInstanceOf('\DateTime', $createdOn, 'getCreation returns DateTime');
        $itemCreationTimestamp = $createdOn->getTimestamp();
        $this->assertEquals($creationTS - 10, $itemCreationTimestamp, 'createdOn is 10 seconds before expiration');
    }

    public function testGetExpiration()
    {
        $expiration = new \DateTime('now');
        $expiration->add(new \DateInterval('P1D'));
        $expirationTS = $expiration->getTimestamp();

        $key = array('getExpiration', 'test');
        $stash = $this->testConstruct($key);

        $this->assertFalse($stash->getExpiration(), 'no record exists yet, return null');

        $stash->set(array('stuff'), $expiration);

        $stash = $this->testConstruct($key);
        $itemExpiration = $stash->getExpiration();
        $this->assertInstanceOf('\DateTime', $itemExpiration, 'getExpiration returns DateTime');
        $itemExpirationTimestamp = $itemExpiration->getTimestamp();
        $this->assertLessThanOrEqual($expirationTS, $itemExpirationTimestamp, 'sometime before explicitly set expiration');
    }

    public function testIsMiss()
    {
        $stash = $this->testConstruct(array('This', 'Should', 'Fail'));
        $this->assertTrue($stash->isMiss(), 'isMiss returns true for missing data');
        $data = $stash->get();
        $this->assertNull($data, 'getData returns null for missing data');

        $key = array('isMiss', 'test');

        $stash = $this->testConstruct($key);
        $stash->set('testString');

        $stash = $this->testConstruct($key);
        $this->assertTrue(!$stash->isMiss(), 'isMiss returns false for valid data');
    }

    public function testClear()
    {
        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct($key);
            $stash->set($value);
            $this->assertAttributeInternalType('string', 'keyString', $stash, 'Argument based keys setup keystring');
            $this->assertAttributeInternalType('array', 'key', $stash, 'Argument based keys setup key');

            $this->assertTrue($stash->set($value), 'Driver class able to store data type ' . $type);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            // Make sure its actually populated. This has the added bonus of making sure one clear doesn't empty the
            // entire cache.
            $stash = $this->testConstruct($key);
            $data = $stash->get();
            $this->assertEquals($value, $data, 'getData ' . $type . ' returns same item as stored after other data is cleared');


            // Run the clear, make sure it says it works.
            $stash = $this->testConstruct($key);
            $this->assertTrue($stash->clear(), 'clear returns true');


            // Finally verify that the data has actually been removed.
            $stash = $this->testConstruct($key);
            $data = $stash->get();
            $this->assertNull($data, 'getData ' . $type . ' returns null once deleted');
            $this->assertTrue($stash->isMiss(), 'isMiss returns true for deleted data');
        }

        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct($key);
            $stash->set($value);
        }

        // clear
        $stash = $this->testConstruct();
        $this->assertTrue($stash->clear(), 'clear returns true');

        // make sure all the keys are gone.
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            // Finally verify that the data has actually been removed.
            $stash = $this->testConstruct($key);
            $data = $stash->get();
            $this->assertNull($data, 'getData ' . $type . ' returns null once deleted');
            $this->assertTrue($stash->isMiss(), 'isMiss returns true for deleted data');
        }
    }

    public function testExtend()
    {
        $this->driver = null;
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            $stash = $this->testConstruct();
            $stash->clear();


            $stash = $this->testConstruct($key);
            $stash->set($value, -600);

            $stash = $this->testConstruct($key);
            $this->assertTrue($stash->extend(), 'extend returns true');

            $stash = $this->testConstruct($key);
            $data = $stash->get();
            $this->assertEquals($value, $data, 'getData ' . $type . ' returns same item as stored and extended');
            $this->assertFalse($stash->isMiss(), 'getData ' . $type . ' returns false for isMiss');
        }
    }


    public function testDisable()
    {
        $stash = $this->testConstruct(array('path', 'to', 'key'));
        $stash->disable();
        $this->assertDisabledStash($stash);
    }

    public function testDisableCacheWillNeverCallDriver()
    {
        $item = $this->getItem();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($this->getMockedDriver());
        $item->setPool($poolStub);
        $item->setKey(array('test', 'key'));
        $item->disable();

        $this->assertTrue($item->isDisabled());
        $this->assertDisabledStash($item);
    }

    public function testDisableCacheGlobally()
    {
        Item::$runtimeDisable = true;
        $testDriver = $this->getMockedDriver();

        $item = $this->getItem();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($this->getMockedDriver());
        $item->setPool($poolStub);
        $item->setKey(array('test', 'key'));

        $this->assertDisabledStash($item);
        $this->assertTrue($item->isDisabled());
        $this->assertFalse($testDriver->wasCalled(), 'Driver was not called after Item was disabled.');
        Item::$runtimeDisable = false;
    }

    private function getMockedDriver()
    {
        return new \Stash\Test\Stubs\DriverCallCheckStub();
    }

    private function assertDisabledStash(\Stash\Interfaces\ItemInterface $item)
    {
        $this->assertFalse($item->set('true'), 'storeData returns false for disabled cache');
        $this->assertNull($item->get(), 'getData returns null for disabled cache');
        $this->assertFalse($item->clear(), 'clear returns false for disabled cache');
        $this->assertTrue($item->isMiss(), 'isMiss returns true for disabled cache');
        $this->assertFalse($item->extend(), 'extend returns false for disabled cache');
        $this->assertTrue($item->lock(100), 'lock returns true for disabled cache');
    }
}
