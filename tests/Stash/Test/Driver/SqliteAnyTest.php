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
use Stash\Driver\Sqlite;
use Stash\Item;
use Stash\Pool;
use Stash\Utilities;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqliteAnyTest extends \PHPUnit\Framework\TestCase
{
    protected $driverClass = 'Stash\Driver\Sqlite';

    protected function setUp()
    {
        $driverClass = $this->driverClass;

        if (!$driverClass::isAvailable()) {
            $this->markTestSkipped('Driver class unsuited for current environment');

            return;
        }
    }

    public function testConstruction()
    {
        $key = array('apple', 'sauce');

        $driver = new Sqlite(array());
        $pool = new Pool();
        $pool->setDriver($driver);
        $item = $pool->getItem('testKey');
        $item->set($key);
        $this->assertTrue($pool->save($item), 'Able to load and store with unconfigured extension.');
    }

    public static function tearDownAfterClass()
    {
        Utilities::deleteRecursive(Utilities::getBaseDirectory());
    }
}
