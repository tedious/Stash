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

use Stash\Driver\BlackHole;

/**
 * @author  Benjamin Zikarsky <benjamin.zikarsky@perbility.de>
 */
class BlackHoleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Stash\Driver\BlackHole
     */
    private $driver = null;

    public function setUp()
    {
        $this->driver = new BlackHole();
    }

    public function testPurge()
    {
        $this->assertTrue($this->driver->purge());
    }

    public function testStoreData()
    {
        $this->assertTrue($this->driver->storeData("test", "data", 0));
        $this->assertFalse($this->driver->getData("test"));
    }

    public function testGetData()
    {
        $this->assertFalse($this->driver->getData("test"));
    }

    public function testClear()
    {
        $this->assertTrue($this->driver->clear());
        $this->assertTrue($this->driver->clear(null));
        $this->assertTrue($this->driver->clear("test"));
    }
}
