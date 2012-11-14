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
class MemcachedTest extends MemcacheTest
{
    protected $extension = 'memcached';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $memcachedOptions = array('hash' => 'default',
                                  'distribution' => 'modula',
                                  'serializer' => 'php',
                                  'buffer_writes' => false,
                                  'connect_timeout' => 500,
                                  'prefix_key' => 'cheese'
        );

        return array_merge($options, $memcachedOptions);
    }
}
