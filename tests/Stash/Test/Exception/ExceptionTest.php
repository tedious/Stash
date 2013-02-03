<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Exception;

use Stash;
use Stash\Driver\DriverInterface;

/**
 * ExceptionTest is used for testing how Stash reacts to thrown errors. Every function but the constructor throws
 * an exception.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @codeCoverageIgnore
 */
class ExceptionTest implements DriverInterface
{
    protected $store = array();

    public function __construct(array $options = array())
    {

    }

    public function __destruct()
    {
    }

    public function getData($key)
    {
        throw new TestException('Test exception for ' . __FUNCTION__ . ' call');
    }

    protected function getKeyIndex($key)
    {
        throw new TestException('Test exception for ' . __FUNCTION__ . ' call');
    }

    public function storeData($key, $data, $expiration)
    {
        throw new TestException('Test exception for ' . __FUNCTION__ . ' call');
    }

    public function clear($key = null)
    {
        throw new TestException('Test exception for ' . __FUNCTION__ . ' call');
    }

    public function purge()
    {
        throw new TestException('Test exception for ' . __FUNCTION__ . ' call');
    }

    public function canEnable()
    {
        return (defined('TESTING') && TESTING);
    }

    static public function isAvailable()
    {
        return (defined('TESTING') && TESTING);
    }
}
