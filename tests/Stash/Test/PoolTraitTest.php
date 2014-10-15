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

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @requires PHP 5.4
 */
class PoolTraitTest extends AbstractPoolTest
{
    protected $poolClass = '\Stash\Test\Stubs\PoolTraitStub';

    public function testSetItemClass()
    {
        $mockItem = $this->getMock('Stash\Interfaces\ItemInterface');
        $mockClassName = get_class($mockItem);
        $pool = $this->getTestPool();

        $this->assertTrue($pool->setItemClass($mockClassName));
        $this->assertAttributeEquals($mockClassName, 'itemClass', $pool->getPool());
    }

    public function testNamespacing()
    {
        $pool = $this->getTestPool();

        $this->assertAttributeEquals(null, 'namespace', $pool->getPool(), 'Namespace starts empty.');
        $this->assertTrue($pool->setNamespace('TestSpace'), 'setNamespace returns true.');
        $this->assertAttributeEquals('TestSpace', 'namespace', $pool->getPool(), 'setNamespace sets the namespace.');
        $this->assertEquals('TestSpace', $pool->getNamespace(), 'getNamespace returns current namespace.');

        $this->assertTrue($pool->setNamespace(), 'setNamespace returns true when setting null.');
        $this->assertAttributeEquals(null, 'namespace', $pool->getPool(), 'setNamespace() empties namespace.');
        $this->assertFalse($pool->getNamespace(), 'getNamespace returns false when no namespace is set.');
    }

    public function testSetLogger()
    {
        $pool = $this->getTestPool();

        $driver = new DriverExceptionStub();
        $pool->setDriver($driver);

        $logger = new LoggerStub();
        $pool->setLogger($logger);

        $this->assertAttributeInstanceOf('Stash\Test\Stubs\LoggerStub', 'logger', $pool->getPool(), 'setLogger injects logger into Item.');
    }
}
