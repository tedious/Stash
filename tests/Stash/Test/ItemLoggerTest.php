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

use Stash\Test\Stubs\LoggerStub;
use Stash\Test\Stubs\DriverExceptionStub;

use Stash\Item;
use Stash\Utilities;
use Stash\Driver\Ephemeral as Ephemeral;



/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class ItemLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testSetLogger()
    {
        $driver = new Ephemeral();
        $item = new Item($driver, array('path', 'to', 'constructor'));
        $logger = new LoggerStub();
        $item->setLogger($logger);

        $this->assertAttributeInstanceOf('Stash\Test\Stubs\LoggerStub', 'logger', $item, 'setLogger injects logger into Item.');
    }

    public function testGet()
    {
        $driver = new DriverExceptionStub();
        $item = new Item($driver, array('path', 'to', 'get'));
        $logger = new LoggerStub();
        $item->setLogger($logger);

        // triggerlogging
        $item->get('test_key');

        $this->assertInstanceOf('Stash\Test\Exception\TestException',
                                $logger->lastContext['exception'], 'Logger was passed exception in event context.');

        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "get" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }

    public function testSet()
    {
        $driver = new DriverExceptionStub();
        $item = new Item($driver, array('path', 'to', 'set'));
        $logger = new LoggerStub();
        $item->setLogger($logger);

        // triggerlogging
        $item->set('test_key');

        $this->assertInstanceOf('Stash\Test\Exception\TestException',
                                $logger->lastContext['exception'], 'Logger was passed exception in event context.');
        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "set" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }

    public function testClear()
    {
        $driver = new DriverExceptionStub();
        $item = new Item($driver, array('path', 'to', 'clear'));
        $logger = new LoggerStub();
        $item->setLogger($logger);

        // triggerlogging
        $item->clear();

        $this->assertInstanceOf('Stash\Test\Exception\TestException',
                                $logger->lastContext['exception'], 'Logger was passed exception in event context.');
        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "clear" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }
}
