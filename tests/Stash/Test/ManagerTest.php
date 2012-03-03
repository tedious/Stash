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

use Stash\Manager;
use Stash\Handler\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array(array('test', 'test'));

    public function testSetHandler()
    {
        $stash = Manager::getCache('base');
        $this->assertInstanceOf('Stash\Cache', $stash, 'Unprimed Stash\Manager returns memory based stash.');

        Manager::setHandler('base', new Ephemeral(array()));
        $stash = Manager::getCache('base');
        $this->assertAttributeInstanceOf('Stash\Handler\Ephemeral', 'handler', $stash, 'set handler is pushed to new stash objects');
    }

    public function testGetCache()
    {
        $stash = Manager::getCache('base', 'one');
        $this->assertInstanceOf('Stash\Cache', $stash, 'getCache returns a Stash\Cache object');
        $stash->set($this->data);
        $storedData = $stash->get();
        $this->assertEquals($this->data, $storedData, 'getCache returns working Stash object');
    }

    public function testClearCache()
    {
        $stash = Manager::getCache('base', 'one');
        $stash->set($this->data, -600);
        $this->assertTrue(Manager::clearCache('base', 'one'), 'clear returns true');

        $stash = Manager::getCache('base', 'one');
        $this->assertNull($stash->get(), 'clear removes item');
        $this->assertTrue($stash->isMiss(), 'clear causes cache miss');
    }

    public function testPurgeCache()
    {
        $stash = Manager::getCache('base', 'one');
        $stash->set($this->data, -600);
        $this->assertTrue(Manager::purgeCache('base'), 'purge returns true');

        $stash = Manager::getCache('base', 'one');
        $this->assertNull($stash->get(), 'purge removes item');
        $this->assertTrue($stash->isMiss(), 'purge causes cache miss');
    }

    public function testGetCacheHandlers()
    {
        $handlers = Manager::getCacheHandlers();
        $this->assertTrue(is_array($handlers), '');
    }
}
