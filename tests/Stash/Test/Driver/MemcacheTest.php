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

use Stash\Test\Stubs\PoolGetDriverStub;
use Stash\Driver\Memcache;
use Stash\Item;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class MemcacheTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Memcache';
    protected $extension = 'memcache';

    protected $servers = array('127.0.0.1', '11211');
    protected $persistence = true;

    protected function getOptions()
    {
        return array('extension' => $this->extension, 'servers' => $this->servers);
    }

    protected function setUp()
    {
        if (!$this->setup) {
            $this->startTime = time();
            $this->expiration = $this->startTime + 3600;
            $driverClass = $this->driverClass;

            if (!$driverClass::isAvailable()) {
                $this->markTestSkipped('Driver class unsuited for current environment');

                return;
            }

            if (!class_exists(ucfirst($this->extension))) {
                $this->markTestSkipped('Test requires ' . $this->extension . ' extension');

                return;
            }

            if (!($sock = @fsockopen($this->servers[0], $this->servers[1], $errno, $errstr, 1))) {
                $this->markTestSkipped('Memcache tests require memcache server');

                return;
            }

            fclose($sock);
            $this->data['object'] = new \stdClass();
        }
    }

    public function testIsAvailable()
    {
        $this->assertTrue(\Stash\Driver\Sub\Memcache::isAvailable());
    }

    public function testConstructionOptions()
    {
        $key = array('apple', 'sauce');

        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $options['servers'][] = array('127.0.0.1', '11211');
        $options['extension'] = $this->extension;
        $driver = new Memcache($options);

        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);
        $item->setPool($poolStub);
        $item->setKey($key);

        $this->assertTrue($item->set($key)->save(), 'Able to load and store memcache driver using multiple servers');

        $options = array();
        $options['extension'] = $this->extension;
        $driver = new Memcache($options);
        $item = new Item();
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);
        $item->setPool($poolStub);
        $item->setKey($key);
        $this->assertTrue($item->set($key)->save(), 'Able to load and store memcache driver using default server');
    }
}
