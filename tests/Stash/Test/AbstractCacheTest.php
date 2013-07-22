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

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @todo find out why this has to be abstract to work (see https://github.com/tedivm/Stash/pull/10)
 */
abstract class AbstractCacheTest extends \PHPUnit_Framework_TestCase
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

    public function testConstruct($key = array())
    {
        if (!isset($this->driver)) {
            $this->driver = new Ephemeral(array());
        }

        $stash = new Item($this->driver, $key);
        $this->assertTrue(is_a($stash, 'Stash\Item'), 'Test object is an instance of Stash');
        return $stash;
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

        $stashString = $this->testConstruct($keyString);
        $this->assertAttributeInternalType('string', 'keyString', $stashString, 'Argument based keys setup keystring');
        $this->assertAttributeInternalType('array', 'key', $stashString, 'Array based keys setup key');

        $this->assertAttributeEquals($keyNormalized, 'key', $stashString, 'setupKey from string builds proper key array.');

        $returnedKey = $stashString->getKey();
        $this->assertEquals($keyString, $returnedKey, 'getKey returns the same key as initially passed via string.');


        $stashString = $this->testConstruct('/' . $keyString . '/');
        $returnedKey = $stashString->getKey();
        $this->assertEquals('/' . $keyString . '/', $returnedKey, 'getKey returns the same key as initially passed via string.');
        $this->assertAttributeEquals($keyNormalized, 'key', $stashString, 'setupKey discards trailing and leading slashes.');
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
        $this->assertEquals($$oldValue, $return, 'Old value is returned');
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

    public function testExtendCache()
    {
        $this->driver = null;
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            $stash = $this->testConstruct();
            $stash->clear();


            $stash = $this->testConstruct($key);
            $stash->set($value, -600);

            $stash = $this->testConstruct($key);
            $this->assertTrue($stash->extendCache(), 'extendCache returns true');

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
        $stash = new Item($this->getMockedDriver(), array('test', 'key'));
        $stash->disable();
        $this->assertTrue($stash->isDisabled());
        $this->assertDisabledStash($stash);
    }

    public function testDisableCacheGlobally()
    {
        Item::$runtimeDisable = true;
        $stash = new Item($this->getMockedDriver(), array('test', 'key'));
        $this->assertDisabledStash($stash);
        $this->assertTrue($stash->isDisabled());
        Item::$runtimeDisable = false;
    }

    private function getMockedDriver()
    {
        $driver = $this->getMockBuilder('Stash\Interfaces\DriverInterface')
                        ->getMock();
        foreach (get_class_methods($driver) as $methodName) {
            $driver->expects($this->never())
                    ->method($methodName);
        }

        return $driver;
    }

    private function assertDisabledStash(Item $stash)
    {
        $this->assertFalse($stash->set('true'), 'storeData returns false for disabled cache');
        $this->assertNull($stash->get(), 'getData returns null for disabled cache');
        $this->assertFalse($stash->clear(), 'clear returns false for disabled cache');
        $this->assertTrue($stash->isMiss(), 'isMiss returns true for disabled cache');
        $this->assertFalse($stash->extendCache(), 'extendCache returns false for disabled cache');
        $this->assertTrue($stash->lock(100), 'lock returns true for disabled cache');
    }
}
