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

use Stash\DriverList;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class DriverListTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAvailableDrivers()
    {
        $drivers = DriverList::getAvailableDrivers();
        $this->assertArrayHasKey('FileSystem', $drivers, 'getDrivers returns FileSystem driver');
        $this->assertArrayNotHasKey('Array', $drivers, 'getDrivers doesn\'t return Array driver');

        DriverList::registerDriver('TestUnavailable_getAvailable', '\Stash\Test\Stubs\DriverUnavailableStub');
        $drivers = DriverList::getAvailableDrivers();
        $this->assertArrayNotHasKey('TestUnavailable_getAvailable', $drivers, 'getAllDrivers doesn\'t return TestBroken driver');
    }

    public function testGetAllDrivers()
    {
        DriverList::registerDriver('TestBroken_getAll', 'stdClass');
        $drivers = DriverList::getAllDrivers();
        $this->assertArrayNotHasKey('TestBroken_getAll', $drivers, 'getAllDrivers doesn\'t return TestBroken driver');

        DriverList::registerDriver('TestUnavailable_getAll', '\Stash\Test\Stubs\DriverUnavailableStub');
        $drivers = DriverList::getAllDrivers();
        $this->assertArrayHasKey('TestUnavailable_getAll', $drivers, 'getAllDrivers doesn\'t return TestBroken driver');
    }

    public function testRegisterDriver()
    {
        DriverList::registerDriver('Array', 'Stash\Driver\Ephemeral');

        $drivers = DriverList::getAvailableDrivers();
        $this->assertArrayHasKey('Array', $drivers, 'getDrivers returns Array driver');
    }

    public function testGetDriverClass()
    {
        $this->assertEquals('Stash\Driver\Ephemeral', DriverList::getDriverClass('Array'), 'getDriverClass returns proper classname for Array driver');

        $this->assertFalse(DriverList::getDriverClass('FakeName'), 'getDriverClass returns false for nonexistent class.');
    }
}
