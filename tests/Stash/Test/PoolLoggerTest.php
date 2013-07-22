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

use Stash\Test\Stubs\LoggerStub as LoggerStub;
use Stash\Test\Exception\ExceptionTest as ExceptionTest;

use Stash\Pool;
use Stash\Utilities;
use Stash\Driver\Ephemeral as Ephemeral;



/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class PoolLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testSetLogger()
    {
        $driver = new Ephemeral();
        $pool = new Pool($driver);
        $logger = new LoggerStub();
        $pool->setLogger($logger);

        $this->assertAttributeInstanceOf('Stash\Test\Stubs\LoggerStub', 'logger', $pool, 'setLogger injects logger into Item.');
    }

    public function testFlush()
    {
        $driver = new ExceptionTest();
        $pool = new Pool($driver);
        $logger = new LoggerStub();
        $pool->setLogger($logger);

        // triggerlogging
        $pool->flush();

        $this->assertInstanceOf('Stash\Test\Exception\TestException',
                                $logger->lastContext['exception'], 'Logger was passed exception in event context.');

        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "get" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }

    public function testPurge()
    {
        $driver = new ExceptionTest();
        $pool = new Pool($driver);
        $logger = new LoggerStub();
        $pool->setLogger($logger);

        // triggerlogging
        $pool->purge();

        $this->assertInstanceOf('Stash\Test\Exception\TestException',
                                $logger->lastContext['exception'], 'Logger was passed exception in event context.');
        $this->assertTrue(strlen($logger->lastMessage) > 0, 'Logger message set after "set" exception.');
        $this->assertEquals('critical', $logger->lastLevel, 'Exceptions logged as critical.');
    }

}
