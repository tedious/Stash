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

use Stash\Cache;
use Stash\Utilities;
use Stash\Handler\Ephemeral;

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
    protected $handler;

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

    public function testConstruct()
    {
        if (!isset($this->handler)) {
            $this->handler = new Ephemeral(array());
        }

        $stash = new Cache($this->handler);
        $this->assertTrue(is_a($stash, 'Stash\Cache'), 'Test object is an instance of Stash');
        return $stash;
    }

    public function testSetupKey()
    {
        $key = array('this', 'is', 'the', 'key');

        $stash = $this->testConstruct();
        $stash->setupKey(array('this', 'is', 'the', 'key'));
        $this->assertAttributeInternalType('string', 'keyString', $stash, 'Argument based keys setup keystring');
        $this->assertAttributeInternalType('array', 'key', $stash, 'Array based keys setup keu');


        $stash = $this->testConstruct();
        $stash->setupKey('this', 'is', 'the', 'key');
        $this->assertAttributeInternalType('string', 'keyString', $stash, 'Argument based keys setup keystring');
        $this->assertAttributeInternalType('array', 'key', $stash, 'Argument based keys setup key');

        $this->setExpectedException('\Stash\Exception\InvalidArgumentException', 'No key sent to the cache constructor.');
        $stash->setupKey();
    }

    public function testSet()
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $this->assertAttributeInternalType('string', 'keyString', $stash, 'Argument based keys setup keystring');
            $this->assertAttributeInternalType('array', 'key', $stash, 'Argument based keys setup key');

            $this->assertTrue($stash->set($value), 'Handler class able to store data type ' . $type);
        }
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $data = $stash->get();
            $this->assertEquals($value, $data, 'getData ' . $type . ' returns same item as stored');
        }
    }

    public function testInvalidation()
    {
        $key = array('path', 'to', 'item');
        $oldValue = 'oldValue';
        $newValue = 'newValue';


        $runningStash = $this->testConstruct();
        $runningStash->setupKey($key);
        $runningStash->set($oldValue, -300);


        // Test without stampede
        $controlStash = $this->testConstruct();
        $controlStash->setupKey($key);

        $return = $controlStash->get(Cache::SP_VALUE, $newValue);
        $this->assertEquals($oldValue, $return, 'Old value is returned');
        $this->assertTrue($controlStash->isMiss());
        unset($controlStash);


        // Enable stampede control
        $runningStash->lock();
        $this->assertAttributeEquals(true, 'stampedeRunning', $runningStash, 'Stampede flag is set.');


        // Old
        $oldStash = $this->testConstruct();
        $oldStash->setupKey($key);

        $return = $oldStash->get(Cache::SP_OLD);
        $this->assertEquals($oldValue, $return, 'Old value is returned');
        $this->assertFalse($oldStash->isMiss());
        unset($oldStash);

        // Value
        $valueStash = $this->testConstruct();
        $valueStash->setupKey($key);

        $return = $valueStash->get(Cache::SP_VALUE, $newValue);
        $this->assertEquals($newValue, $return, 'New value is returned');
        $this->assertFalse($valueStash->isMiss());
        unset($valueStash);


        // Sleep
        $sleepStash = $this->testConstruct();
        $sleepStash->setupKey($key);

        $start = microtime(true);
        $return = $sleepStash->get(array(Cache::SP_SLEEP, 250, 2));
        $end = microtime(true);

        $this->assertTrue($sleepStash->isMiss());
        $sleepTime = ($end - $start) * 1000;

        $this->assertGreaterThan(500, $sleepTime, 'Sleep method sleeps for required time.');
        $this->assertLessThan(510, $sleepTime, 'Sleep method does not oversleep.');

        unset($sleepStash);


        // Unknown - if a random, unknown method is passed for invalidation we should rely on the default method
        $unknownStash = $this->testConstruct();
        $unknownStash->setupKey($key);

        $return = $unknownStash->get(78);
        $this->assertEquals($$oldValue, $return, 'Old value is returned');
        $this->assertTrue($unknownStash->isMiss(), 'Cache is marked as miss');
        unset($unknownStash);


        // Test that storing the cache turns off stampede mode.
        $runningStash->set($newValue, 30);
        $this->assertAttributeEquals(false, 'stampedeRunning', $runningStash, 'Stampede flag is off.');
        unset($runningStash);


        // Precompute - test outside limit
        $precomputeStash = $this->testConstruct();
        $precomputeStash->setupKey($key);

        $return = $precomputeStash->get(Cache::SP_PRECOMPUTE, 10);
        $this->assertFalse($precomputeStash->isMiss(), 'Cache is marked as hit');
        unset($precomputeStash);

        // Precompute - test inside limit
        $precomputeStash = $this->testConstruct();
        $precomputeStash->setupKey($key);

        $return = $precomputeStash->get(Cache::SP_PRECOMPUTE, 35);
        $this->assertTrue($precomputeStash->isMiss(), 'Cache is marked as miss');
        unset($precomputeStash);

    }

    public function testSetWithDateTime()
    {

        $expiration = new \DateTime('now');
        $expiration->add(new \DateInterval('P1D'));

        $key = array('base', 'expiration', 'test');
        $stash = $this->testConstruct();
        $stash->setupKey($key);
        $stash->set(array(1, 2, 3, 'apples'), $expiration);

        $stash = $this->testConstruct();
        $stash->setupKey($key);
        $data = $stash->get();
        $this->assertEquals(array(1, 2, 3, 'apples'), $data, 'getData returns data stores using a datetime expiration');

    }

    public function testIsMiss()
    {
        $stash = $this->testConstruct();
        $stash->setupKey(array('This', 'Should', 'Fail'));
        $data = $stash->get();

        $this->assertNull($data, 'getData returns null for missing data');
        $this->assertTrue($stash->isMiss(), 'isMiss returns true for missing data');
    }

    public function testClear()
    {
        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $stash->set($value);
            $this->assertAttributeInternalType('string', 'keyString', $stash, 'Argument based keys setup keystring');
            $this->assertAttributeInternalType('array', 'key', $stash, 'Argument based keys setup key');

            $this->assertTrue($stash->set($value), 'Handler class able to store data type ' . $type);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            // Make sure its actually populated. This has the added bonus of making sure one clear doesn't empty the
            // entire cache.
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $data = $stash->get();
            $this->assertEquals($value, $data, 'getData ' . $type . ' returns same item as stored after other data is cleared');


            // Run the clear, make sure it says it works.
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $this->assertTrue($stash->clear(), 'clear returns true');


            // Finally verify that the data has actually been removed.
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $data = $stash->get();
            $this->assertNull($data, 'getData ' . $type . ' returns null once deleted');
            $this->assertTrue($stash->isMiss(), 'isMiss returns true for deleted data');
        }

        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $stash->set($value);
        }

        // clear
        $stash = $this->testConstruct();
        $this->assertTrue($stash->clear(), 'clear returns true');

        // make sure all the keys are gone.
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            // Finally verify that the data has actually been removed.
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $data = $stash->get();
            $this->assertNull($data, 'getData ' . $type . ' returns null once deleted');
            $this->assertTrue($stash->isMiss(), 'isMiss returns true for deleted data');
        }
    }

    public function testPurge()
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', 'fresh', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $stash->set($value);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', 'stale', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $stash->set($value, -600);
        }

        $this->assertTrue($stash->purge());


        foreach ($this->data as $type => $value) {
            $key = array('base', 'stale', $type);
            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $data = $stash->get();
            $this->assertNull($data, 'getData ' . $type . ' returns null once purged');
            $this->assertTrue($stash->isMiss(), 'isMiss returns true for purged data');
        }
    }

    public function testExtendCache()
    {
        unset($this->handler);
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);

            $stash = $this->testConstruct();
            $stash->clear();


            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $stash->set($value, -600);

            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $this->assertTrue($stash->extendCache(), 'extendCache returns true');

            $stash = $this->testConstruct();
            $stash->setupKey($key);
            $data = $stash->get();
            $this->assertEquals($value, $data, 'getData ' . $type . ' returns same item as stored and extended');
            $this->assertFalse($stash->isMiss(), 'getData ' . $type . ' returns false for isMiss');
        }
    }


    public function testDisable()
    {
        $stash = $this->testConstruct();
        $stash->disable();
        $stash->setupKey(array('path', 'to', 'key'));

    }

    public function testDisableCacheWillNeverCallHandler()
    {
        $stash = new Cache($this->getMockedHandler());
        $stash->disable();
        $this->assertTrue($stash->isDisabled());
        $this->assertDisabledStash($stash);
    }

    public function testDisableCacheGlobally()
    {
        Cache::$runtimeDisable = true;
        $stash = new Cache($this->getMockedHandler());
        $this->assertDisabledStash($stash);
        $this->assertTrue($stash->isDisabled());
        Cache::$runtimeDisable = false;
    }

    public function testMemoryOnlyWithInstanceFlag()
    {
        $stash = new Cache($this->getMockedHandler());
        $stash->storeInMemoryOnly();
        $this->assertTrue($stash->isMemoryOnly());
        $stash->storeInMemoryOnly(false);
        $this->assertFalse($stash->isMemoryOnly());
        $stash->storeInMemoryOnly(true);
        $this->assertTrue($stash->isMemoryOnly());
        $this->assertMemoryOnlyStash($stash);
    }

    public function testMemoryOnlyWithoutHandler()
    {
        $stash = new Cache();
        $this->assertTrue($stash->isMemoryOnly());
        $this->assertMemoryOnlyStash($stash);
    }

    private function getMockedHandler()
    {
        $handler = $this->getMockBuilder('Stash\Handler\HandlerInterface')
                        ->getMock();
        foreach (get_class_methods($handler) as $methodName) {
            $handler->expects($this->never())
                    ->method($methodName);
        }

        return $handler;
    }

    private function assertMemoryOnlyStash(Cache $stash)
    {
        $this->assertFalse($stash->set('true'), 'storeData returns false for memory only cache');
        $this->assertNull($stash->get(), 'getData returns null for memory only cache');
        $this->assertTrue($stash->clear(), 'clear returns true for memory only cache');
        $this->assertTrue($stash->purge(), 'purge returns true for memory only cache');
        $this->assertTrue($stash->isMiss(), 'isMiss returns true for memory only cache');
        $this->assertFalse($stash->extendCache(), 'extendCache returns false for memory only cache');
        $this->assertTrue($stash->lock(100), 'lock returns true in memory only cache');
    }

    private function assertDisabledStash(Cache $stash)
    {
        $this->assertFalse($stash->set('true'), 'storeData returns false for disabled cache');
        $this->assertNull($stash->get(), 'getData returns null for disabled cache');
        $this->assertFalse($stash->clear(), 'clear returns false for disabled cache');
        $this->assertFalse($stash->purge(), 'purge returns false for disabled cache');
        $this->assertTrue($stash->isMiss(), 'isMiss returns true for disabled cache');
        $this->assertFalse($stash->extendCache(), 'extendCache returns false for disabled cache');
        $this->assertTrue($stash->lock(100), 'lock returns true for disabled cache');
    }
}
