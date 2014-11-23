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

use Stash\Item;
use Stash\Test\Stubs\PoolGetDriverStub;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class EphemeralTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Ephemeral';

    public function testKeyCollisions1()
    {
        $driver = new $this->driverClass;
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);

        $item1 = new Item();
        $item1->setPool($poolStub);
        $item1->setKey(array('##', '#'));
        $item1->set('X');

        $item2 = new Item();
        $item2->setPool($poolStub);
        $item2->setKey(array('#', '##'));
        $item2->set('Y');

        $this->assertEquals('X', $item1->get());
    }

    public function testKeyCollisions2()
    {
        $driver = new $this->driverClass;
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);

        $item1 = new Item();
        $item1->setPool($poolStub);
        $item1->setKey(array('#'));
        $item1->set('X');

        $item2 = new Item();
        $item2->setPool($poolStub);
        $item2->setKey(array(':'));
        $item2->set('Y');

        $this->assertEquals('X', $item1->get());
    }
}
