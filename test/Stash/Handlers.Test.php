<?php

class StashHandlersTest extends PHPUnit_Framework_TestCase
{
	public function testGetHandlers()
	{
		$handlers = Stash\Handlers::getHandlers();
		$this->assertArrayHasKey('FileSystem', $handlers, 'getHandlers returns FileSystem handler');
		$this->assertArrayNotHasKey('Array', $handlers, 'getHandlers doesn\'t return Array handler');
	}

	public function testRegisterHandler()
	{
		Stash\Handlers::registerHandler('Array', 'Stash\Handlers\Ephemeral');

		$handlers = Stash\Handlers::getHandlers();
		$this->assertArrayHasKey('Array', $handlers, 'getHandlers returns Array handler');
	}

	public function testGetHandlerClass()
	{
		Stash\Handlers::getHandlerClass('Array');

		$this->assertEquals('Stash\Handlers\Ephemeral', Stash\Handlers::getHandlerClass('Array'),
							'getHandlerClass returns proper classname for Array handler');
	}

}

?>