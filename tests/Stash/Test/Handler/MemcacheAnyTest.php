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

use Stash\Handler\Memcache;
use Stash\Item;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class MemcacheAnyTest extends \PHPUnit_Framework_TestCase
{
    protected $handlerClass = 'Stash\Handler\Memcache';

    protected $servers = array('127.0.0.1', '11211');

    protected function setUp()
    {
        $handlerClass = $this->handlerClass;

        if (!$handlerClass::isAvailable()) {
            $this->markTestSkipped('Handler class unsuited for current environment');
            return;
        }

        if (!($sock = @fsockopen($this->servers[0], $this->servers[1], $errno, $errstr, 1))) {
            $this->markTestSkipped('Memcache tests require memcache server');
            return;
        }

        fclose($sock);
    }

    public function testConstruction()
    {
        $key = array('apple', 'sauce');

        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $handler = new Memcache($options);

        $stash = new Item($handler);
        $stash->setupKey($key);
        $this->assertTrue($stash->set($key), 'Able to load and store with unconfigured extension.');
    }
}
