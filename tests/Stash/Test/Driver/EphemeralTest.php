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

    public function testMemoryLimitCanBeSet()
    {
        $usedMemory = memory_get_usage(true);

        /**
         * @var \Stash\Driver\Ephemeral
         */
        $driver = new $this->driverClass([
            'memoryLimit' => $usedMemory + 1024
        ]);

        $expire = time() + 100;
        $driver->storeData(['hello'], 'world', $expire);
        $this->assertNotFalse($driver->getData(['hello']));
    }

    /**
     * @expectedException \Stash\Exception\InvalidArgumentException
     */
    public function testSettingInvalidMemoryLimitThrows()
    {
        new $this->driverClass([
            'memoryLimit' => 'nonsense',
        ]);
    }

    public function testEvictionCausedByMemoryLimit()
    {
        $expire = time() + 100;

        $anObject = (object)['foo' => PHP_INT_MAX, 'bar' => 42];

        $memoryForThreeKeys = (function () use ($anObject, $expire) {
            $memoryUsage = memory_get_usage();

            $driver = new $this->driverClass();
            $driver->storeData(['key1'], clone $anObject, $expire);
            $driver->storeData(['key2'], clone $anObject, $expire);
            $driver->storeData(['key3'], clone $anObject, $expire);

            return memory_get_usage() - $memoryUsage;
        })();

        /**
         * @var \Stash\Driver\Ephemeral
         */
        $driver = new $this->driverClass([
            'memoryLimit' => memory_get_usage() + $memoryForThreeKeys
        ]);

        $driver->storeData(['key1'], clone $anObject, $expire);
        $driver->storeData(['key2'], clone $anObject, $expire);
        $driver->storeData(['key3'], clone $anObject, $expire);
        $driver->storeData(['key4'], clone $anObject, $expire);

        $this->assertFalse($driver->getData(['key1'])); // evicted
        $this->assertNotFalse($driver->getData(['key2']));
        $this->assertNotFalse($driver->getData(['key3']));
        $this->assertNotFalse($driver->getData(['key4']));
    }
}
