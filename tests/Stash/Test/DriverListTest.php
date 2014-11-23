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
class DriverListTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAvailableDrivers()
    {
        $drivers = DriverList::getAvailableDrivers();
        $this->assertArrayHasKey('FileSystem', $drivers, 'getDrivers returns FileSystem driver');
        $this->assertArrayNotHasKey('Array', $drivers, 'getDrivers doesn\'t return Array driver');
    }

    public function testGetDrivers()
    {
        $availableDrivers = DriverList::getAvailableDrivers();
        $getDrivers = DriverList::getDrivers();
        $this->assertEquals($availableDrivers, $getDrivers, 'getDrivers is an alias for getAvailableDrivers');
    }

    public function testRegisterDriver()
    {
        DriverList::registerDriver('Array', 'Stash\Driver\Ephemeral');

        $drivers = DriverList::getDrivers();
        $this->assertArrayHasKey('Array', $drivers, 'getDrivers returns Array driver');
    }

    public function testGetDriverClass()
    {
        $this->assertEquals('Stash\Driver\Ephemeral', DriverList::getDriverClass('Array'), 'getDriverClass returns proper classname for Array driver');

        $this->assertFalse(DriverList::getDriverClass('FakeName'), 'getDriverClass returns false for nonexistent class.');
    }
}
