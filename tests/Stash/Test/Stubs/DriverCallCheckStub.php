<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Stubs;

use Stash;
use Stash\Interfaces\DriverInterface;

/**
 * DriverExceptionStub is used for testing how Stash reacts to thrown errors. Every function but the constructor throws
 * an exception.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @codeCoverageIgnore
 */
class DriverCallCheckStub implements DriverInterface
{
    protected $store = array();
    protected $wasCalled = false;

    public function setOptions(array $options = array())
    {
    }

    public function __destruct()
    {
    }

    public function getData($key)
    {
        $this->wasCalled = true;
    }

    protected function getKeyIndex($key)
    {
        $this->wasCalled = true;
    }

    public function storeData($key, $data, $expiration)
    {
        $this->wasCalled = true;
    }

    public function clear($key = null)
    {
        $this->wasCalled = true;
    }

    public function purge()
    {
        $this->wasCalled = true;
    }

    public function wasCalled()
    {
        return $this->wasCalled;
    }

    public function canEnable()
    {
        return (defined('TESTING') && TESTING);
    }

    public static function isAvailable()
    {
        return (defined('TESTING') && TESTING);
    }
}
