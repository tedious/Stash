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
use Stash\Test\Stubs\PoolGetDriverStub;

use Stash\Item;
use Stash\Driver\Ephemeral as Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class ItemLoggerTest extends \PHPUnit_Framework_TestCase
{
    protected function getItem($key, $exceptionDriver = false)
    {
        if ($exceptionDriver) {
            $fullDriver = 'Stash\Test\Stubs\DriverExceptionStub';
        } else {
            $fullDriver = 'Stash\Driver\Ephemeral';
        }

        $item = new Item();

        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver(new $fullDriver());
        $item->setPool($poolStub);
        $item->setKey($key);

        return $item;
    }

    public function testSetLogger()
    {
        $item = $this->getItem(array('path', 'to', 'constructor'));

        $logger = new LoggerStub();
        $item->setLogger($logger);
        $this->assertAttributeInstanceOf('Stash\Test\Stubs\LoggerStub', 'logger', $item, 'setLogger injects logger into Item.');
    }

    public function testGet()
    {
        $logger = new LoggerStub();

        $item = $this->getItem(array('path', 'to', 'get'), true);
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
        $logger = new LoggerStub();

        $item = $this->getItem(array('path', 'to', 'set'), true);
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
        $logger = new LoggerStub();

        $item = $this->getItem(array('path', 'to', 'clear'), true);
        $item->setLogger($logger);

        // triggerlogging
        $item->clear();

        $this->assertInstanceOf('Stash\Test\Exception\TestException',
                                $logger->lastContext['exception'], 'Logger was passed exception in event context.');
        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "clear" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }
}
