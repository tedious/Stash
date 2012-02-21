<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test;

use Stash\Handlers;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class HandlersTest extends \PHPUnit_Framework_TestCase
{
    public function testGetHandlers()
    {
        $handlers = Handlers::getHandlers();
        $this->assertArrayHasKey('FileSystem', $handlers, 'getHandlers returns FileSystem handler');
        $this->assertArrayNotHasKey('Array', $handlers, 'getHandlers doesn\'t return Array handler');
    }

    public function testRegisterHandler()
    {
        Handlers::registerHandler('Array', 'Stash\Handler\Ephemeral');

        $handlers = Handlers::getHandlers();
        $this->assertArrayHasKey('Array', $handlers, 'getHandlers returns Array handler');
    }

    public function testGetHandlerClass()
    {
        Handlers::getHandlerClass('Array');

        $this->assertEquals('Stash\Handler\Ephemeral', Handlers::getHandlerClass('Array'), 'getHandlerClass returns proper classname for Array handler');
    }

}
