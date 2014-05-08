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

        $item1 = new Item();
        $item1->setDriver(($driver));
        $item1->setKey(array('##' , '#'));
        $item1->set('X');

        $item2 = new Item();
        $item2->setDriver(($driver));
        $item2->setKey(array('#' , '##'));
        $item2->set('Y');

        $this->assertEquals('X', $item1->get());
    }

    public function testKeyCollisions2()
    {
        $driver = new $this->driverClass;

        $item1 = new Item();
        $item1->setDriver(($driver));
        $item1->setKey(array('#'));
        $item1->set('X');

        $item2 = new Item();
        $item2->setDriver(($driver));
        $item2->setKey(array(':'));
        $item2->set('Y');

        $this->assertEquals('X', $item1->get());
    }
}
