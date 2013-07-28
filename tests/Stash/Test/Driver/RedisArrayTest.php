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

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RedisArrayTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Redis';

    protected function setUp()
    {
        parent::setUp();

        $redis = new \Redis();
        if($redis->connect('127.0.0.1', 6380, 0.1) == false)
            $this->markTestSkipped('Second Redis Server needed for more tests.');

    }

}
