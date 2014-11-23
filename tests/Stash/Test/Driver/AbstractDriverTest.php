<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver;

use Stash\Utilities;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array('string' => 'Hello world!',
                            'complexString' => "\t\tHello\r\n\r\'\'World!\"\'\\",
                            'int' => 4234,
                            'negint' => -6534,
                            'bigint' => 58635272821786587286382824657568871098287278276543219876543,
                            'float' => 1.8358023545,
                            'negfloat' => -5.7003249023,
                            'false' => false,
                            'true' => true,
                            'null' => null,
                            'array' => array(3, 5, 7),
                            'hashmap' => array('one' => 1, 'two' => 2),
                            'multidemensional array' => array(array(5345),
                                                              array(3, 'hello', false, array('one' => 1, 'two' => 2
                                                              )
                                                              )
                            ),
                            '@node' => 'stuff',
                            'test/of/really/long/key/with/lots/of/children/keys' => true
    );

    protected $expiration;
    protected $driverClass;
    protected $startTime;
    protected $setup = false;

    public static function tearDownAfterClass()
    {
        Utilities::deleteRecursive(Utilities::getBaseDirectory());
    }

    protected function setUp()
    {
        if (!$this->setup) {
            $this->startTime = time();
            $this->expiration = $this->startTime + 3600;

            if (!$this->getFreshDriver()) {
                $this->markTestSkipped('Driver class unsuited for current environment');
            }

            $this->data['object'] = new \stdClass();
            $this->data['large_string'] = str_repeat('apples', ceil(200000 / 6));
        }
    }

    protected function getFreshDriver(array $options = null)
    {
        $driverClass = $this->driverClass;

        if ($options === null) {
            $options = $this->getOptions();
        }

        if (!$driverClass::isAvailable()) {
            return false;
        }

        $driver = new $driverClass();
        $driver->setOptions($options);

        return $driver;
    }

    public function testSetOptions()
    {
        $driverType = $this->driverClass;
        $options = $this->getOptions();
        $driver = new $driverType();
        $driver->setOptions($options);
        $this->assertTrue(is_a($driver, $driverType), 'Driver is an instance of ' . $driverType);
        $this->assertTrue(is_a($driver, '\Stash\Interfaces\DriverInterface'), 'Driver implments the Stash\Driver\DriverInterface interface');

        return $driver;
    }

    protected function getOptions()
    {
        return array();
    }

    /**
     * @depends testSetOptions
     */
    public function testStoreData($driver)
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($driver->storeData($key, $value, $this->expiration), 'Driver class able to store data type ' . $type);
        }

        return $driver;
    }

    /**
     * @depends testStoreData
     */
    public function testGetData($driver)
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $return = $driver->getData($key);

            $this->assertTrue(is_array($return), 'getData ' . $type . ' returns array');

            $this->assertArrayHasKey('expiration', $return, 'getData ' . $type . ' has expiration');
            $this->assertLessThanOrEqual($this->expiration, $return['expiration'], 'getData ' . $type . ' returns same expiration that is equal to or sooner than the one passed.');

            if (!is_null($return['expiration'])) {
                $this->assertGreaterThan($this->startTime, $return['expiration'], 'getData ' . $type . ' returns expiration that after it\'s storage time');
            }

            $this->assertArrayHasKey('data', $return, 'getData ' . $type . ' has data');
            $this->assertEquals($value, $return['data'], 'getData ' . $type . ' returns same item as stored');
        }

        return $driver;
    }

    /**
     * @depends testGetData
     */
    public function testClear($driver)
    {
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $keyString = implode('::', $key);

            $return = $driver->getData($key);
            $this->assertArrayHasKey('data', $return, 'Repopulating ' . $type . ' stores data');
            $this->assertEquals($value, $return['data'], 'Repopulating ' . $type . ' returns same item as stored');

            $this->assertTrue($driver->clear($key), 'clear of ' . $keyString . ' returned true');
            $this->assertFalse($driver->getData($key), 'clear of ' . $keyString . ' removed data');
        }

        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $keyString = implode('::', $key);

            $driver->storeData($key, $value, $this->expiration);

            $return = $driver->getData($key);
            $this->assertArrayHasKey('data', $return, 'Repopulating ' . $type . ' stores data');
            $this->assertEquals($value, $return['data'], 'Repopulating ' . $type . ' returns same item as stored');
        }

        $this->assertTrue($driver->clear(array('base')), 'clear of base node returned true');

        foreach ($this->data as $type => $value) {
            $this->assertFalse($driver->getData(array('base', $type)), 'clear of base node removed data');
        }

        // repopulate
        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $keyString = implode('::', $key);

            $driver->storeData($key, $value, $this->expiration);

            $return = $driver->getData($key);
            $this->assertArrayHasKey('data', $return, 'Repopulating ' . $type . ' stores data');
            $this->assertEquals($value, $return['data'], 'Repopulating ' . $type . ' returns same item as stored');
        }

        $this->assertTrue($driver->clear(), 'clear of root node returned true');

        foreach ($this->data as $type => $value) {
            $this->assertFalse($driver->getData(array('base', $type)), 'clear of root node removed data');
        }

        return $driver;
    }

    /**
     * @depends testClear
     */
    public function testPurge($driver)
    {
        // We're going to populate this with both stale and fresh data, but we're only checking that the stale data
        // is removed. This is to give drivers the flexibility to introduce their own removal algorithms- our only
        // restriction is that they can't keep things for longer than the developers tell them to, but it's okay to
        // remove things early.

        foreach ($this->data as $type => $value) {
            $driver->storeData(array('base', 'fresh', $type), $value, $this->expiration);
        }

        foreach ($this->data as $type => $value) {
            $driver->storeData(array('base', 'stale', $type), $value, $this->startTime - 600);
        }

        $this->assertTrue($driver->purge());

        foreach ($this->data as $type => $value) {
            $this->assertFalse($driver->getData(array('base', 'stale', $type)), 'purge removed stale data');
        }

        return $driver;
    }

    /**
     * @depends testPurge
     */
    public function testDestructor($driver)
    {
        $driver->__destruct();
        $driver=null;
    }
}
