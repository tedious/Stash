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

use Stash\Driver\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class CompositeTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Composite';
    protected $subDrivers;

    protected function getOptions()
    {
        $options = array();
        $options['drivers'][] = new Ephemeral();
        $options['drivers'][] = new Ephemeral();
        $options['drivers'][] = new Ephemeral();
        $this->subDrivers = $options['drivers'];

        return $options;
    }

    public function testStaggeredStore()
    {
        $driver = $this->getFreshDriver();
        $a = $this->subDrivers[0];
        $b = $this->subDrivers[1];
        $c = $this->subDrivers[2];

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($driver->storeData($key, $value, $this->expiration), 'Driver class able to store data type ' . $type);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $return = $c->getData($key);

            $this->assertTrue(is_array($return), 'getData ' . $type . ' returns array');

            $this->assertArrayHasKey('expiration', $return, 'getData ' . $type . ' has expiration');
            $this->assertLessThanOrEqual($this->expiration, $return['expiration'], 'getData ' . $type . ' returns same expiration that is equal to or sooner than the one passed.');

            $this->assertGreaterThan($this->startTime, $return['expiration'], 'getData ' . $type . ' returns expiration that after it\'s storage time');

            $this->assertArrayHasKey('data', $return, 'getData ' . $type . ' has data');
            $this->assertEquals($value, $return['data'], 'getData ' . $type . ' returns same item as stored');
        }
    }

    public function testStaggeredGet()
    {
        $driver = $this->getFreshDriver();
        $a = $this->subDrivers[0];
        $b = $this->subDrivers[1];
        $c = $this->subDrivers[2];

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $this->assertTrue($c->storeData($key, $value, $this->expiration), 'Driver class able to store data type ' . $type);
        }

        foreach ($this->data as $type => $value) {
            $key = array('base', $type);
            $return = $driver->getData($key);

            $this->assertTrue(is_array($return), 'getData ' . $type . ' returns array');

            $this->assertArrayHasKey('expiration', $return, 'getData ' . $type . ' has expiration');
            $this->assertLessThanOrEqual($this->expiration, $return['expiration'], 'getData ' . $type . ' returns same expiration that is equal to or sooner than the one passed.');

            $this->assertGreaterThan($this->startTime, $return['expiration'], 'getData ' . $type . ' returns expiration that after it\'s storage time');

            $this->assertArrayHasKey('data', $return, 'getData ' . $type . ' has data');
            $this->assertEquals($value, $return['data'], 'getData ' . $type . ' returns same item as stored');
        }
    }
}
