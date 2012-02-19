<?php

namespace Stash\Test;

use Stash\Handlers;

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
        Handlers::registerHandler('Array', 'Stash\Handlers\Ephemeral');

        $handlers = Handlers::getHandlers();
        $this->assertArrayHasKey('Array', $handlers, 'getHandlers returns Array handler');
    }

    public function testGetHandlerClass()
    {
        Handlers::getHandlerClass('Array');

        $this->assertEquals('Stash\Handlers\Ephemeral', Handlers::getHandlerClass('Array'), 'getHandlerClass returns proper classname for Array handler');
    }

}
