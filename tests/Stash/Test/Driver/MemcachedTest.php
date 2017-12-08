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

use \Stash\Driver\Memcache;

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

    public function testIsAvailable()
    {
        $this->assertTrue(\Stash\Driver\Sub\Memcached::isAvailable());
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testSetHashException()
    {
        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $options['servers'][] = array('127.0.0.1', '11211');
        $options['hash'] = 'InvalidOption';
        $driver = new Memcache($options);
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testSetDistributionException()
    {
        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $options['servers'][] = array('127.0.0.1', '11211');
        $options['distribution'] = 'InvalidOption';
        $driver = new Memcache($options);
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testSetSerializerException()
    {
        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $options['servers'][] = array('127.0.0.1', '11211');
        $options['serializer'] = 'InvalidOption';
        $driver = new Memcache($options);
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testSetNumberedValueException()
    {
        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $options['servers'][] = array('127.0.0.1', '11211');
        $options['connect_timeout'] = 'InvalidOption';
        $driver = new Memcache($options);
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testSetBooleanValueException()
    {
        $options = array();
        $options['servers'][] = array('127.0.0.1', '11211', '50');
        $options['servers'][] = array('127.0.0.1', '11211');
        $options['cache_lookups'] = 'InvalidOption';
        $driver = new Memcache($options);
    }
}
