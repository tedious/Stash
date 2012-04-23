<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Handler;

use Stash\Handler\Sqlite;
use Stash\Cache;
use Stash\Utilities;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqliteAnyTest extends \PHPUnit_Framework_TestCase
{
    protected $handlerClass = 'Stash\Handler\Sqlite';

    protected function setUp()
    {
        $handlerClass = $this->handlerClass;

        if (!$handlerClass::isAvailable()) {
            $this->markTestSkipped('Handler class unsuited for current environment');
            return;
        }
    }

    public function testConstruction()
    {
        $key = array('apple', 'sauce');

        $options = array();
        $handler = new Sqlite($options);

        $stash = new Cache($handler);
        $stash->setupKey($key);
        $this->assertTrue($stash->set($key), 'Able to load and store with unconfigured extension.');
    }

    public static function tearDownAfterClass()
    {
        Utilities::deleteRecursive(Utilities::getBaseDirectory());
    }
}
