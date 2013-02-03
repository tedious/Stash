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

use Stash\Drivers;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class DriversTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDrivers()
    {
        $drivers = Drivers::getDrivers();
        $this->assertArrayHasKey('FileSystem', $drivers, 'getDrivers returns FileSystem driver');
        $this->assertArrayNotHasKey('Array', $drivers, 'getDrivers doesn\'t return Array driver');
    }

    public function testRegisterDriver()
    {
        Drivers::registerDriver('Array', 'Stash\Driver\Ephemeral');

        $drivers = Drivers::getDrivers();
        $this->assertArrayHasKey('Array', $drivers, 'getDrivers returns Array driver');
    }

    public function testGetDriverClass()
    {
        Drivers::getDriverClass('Array');

        $this->assertEquals('Stash\Driver\Ephemeral', Drivers::getDriverClass('Array'), 'getDriverClass returns proper classname for Array driver');
    }

}
