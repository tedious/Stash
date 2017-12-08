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
    protected $persistence = false;

    public function testKeyCollisions1()
    {
        $driver = new $this->driverClass;
        $poolStub = new PoolGetDriverStub();
        $poolStub->setDriver($driver);

        $item1 = new Item();
        $item1->setPool($poolStub);
        $item1->setKey(array('##', '#'));
        $item1->set('X')->save();

        $item2 = new Item();
        $item2->setPool($poolStub);
        $item2->setKey(array('#', '##'));
        $item2->set('Y')->save();

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

    /**
     * @expectedException \Stash\Exception\InvalidArgumentException
     */
    public function testSettingMaxItems_InvalidArgument_Throws()
    {
        /**
         * @var \Stash\Driver\Ephemeral
         */
        $driver = new $this->driverClass([
          'maxItems' => null,
        ]);
    }

    /**
     * @expectedException \Stash\Exception\InvalidArgumentException
     */
    public function testSettingMaxItems_LessThan0_Throws()
    {
        /**
         * @var \Stash\Driver\Ephemeral
         */
        $driver = new $this->driverClass([
          'maxItems' => -1,
        ]);
    }

    public function testEviction()
    {
        /**
         * @var \Stash\Driver\Ephemeral
         */
        $driver = new $this->driverClass([
          'maxItems' => 1,
        ]);

        $expire = time() + 100;
        $driver->storeData(['fred'], 'tuttle', $expire);
        $this->assertArraySubset(
          ['data' => 'tuttle', 'expiration' => $expire],
          $driver->getData(['fred'])
        );

        $driver->storeData(['foo'], 'bar', $expire);
        $this->assertFalse($driver->getData(['fred']));
        $this->assertArraySubset(
          ['data' => 'bar', 'expiration' => $expire],
          $driver->getData(['foo'])
        );
    }

    public function testNoEvictionWithDefaultOptions()
    {
        /**
         * @var \Stash\Driver\Ephemeral
         */
        $driver = new $this->driverClass();
        $expire = time() + 100;

        for ($i = 1; $i <= 5; ++$i) {
            $driver->storeData(["item$i"], "value$i", $expire);
        }

        for ($i = 1; $i <= 5; ++$i) {
            $this->assertArraySubset(
              ['data' => "value$i", 'expiration' => $expire],
              $driver->getData(["item$i"])
            );
        }
    }
}
