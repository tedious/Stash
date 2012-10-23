<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Handler;

use Stash\Handler\BlackHole;

/**
 * @author  Benjamin Zikarsky <benjamin.zikarsky@perbility.de>
 */
class BlackHoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Stash\Handler\BlackHole
     */
	private $handler = null;
    
    public function setUp()
    {
        $this->handler = new BlackHole();
    }
    
    public function testPurge()
    {
        $this->assertTrue($this->handler->purge());
    }
    
    public function testStoreData()
    {
        $this->assertTrue($this->handler->storeData("test", "data", 0));
        $this->assertFalse($this->handler->getData("test"));
    }
    
    public function testGetData()
    {
        $this->assertFalse($this->handler->getData("test"));
    }
    
    public function testClear()
    {
        $this->assertTrue($this->handler->clear());
        $this->assertTrue($this->handler->clear(null));
        $this->assertTrue($this->handler->clear("test"));
    }
}
