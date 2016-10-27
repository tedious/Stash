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
class RedisSocketTest extends RedisTest
{
    protected function getOptions()
    {
        $socket = '/tmp/redis.sock';

        if(!file_exists($socket)) {
            $this->markTestSkipped("Redis socket not installed");
        }

        return array('servers' => array(
            array('socket' => $socket, 'ttl' => 0.1)
        ));
    }
}
