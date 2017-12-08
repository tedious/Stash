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

use Stash\Test\Stubs\DriverUnavailableStub;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class UnavailableDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testUnavailableDriver()
    {
        new DriverUnavailableStub();
    }
}
