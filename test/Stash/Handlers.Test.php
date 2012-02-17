<?php

class StashHandlersTest extends PHPUnit_Framework_TestCase
{
	public function testGetHandlers()
	{
		$handlers = StashHandlers::getHandlers();
		$this->assertArrayHasKey('FileSystem', $handlers, 'getHandlers returns FileSystem handler');
		$this->assertArrayNotHasKey('Array', $handlers, 'getHandlers doesn\'t return Array handler');
	}

	public function testRegisterHandler()
	{
		StashHandlers::registerHandler('Array', 'StashArray');

		$handlers = StashHandlers::getHandlers();
		$this->assertArrayHasKey('Array', $handlers, 'getHandlers returns Array handler');
	}

	public function testGetHandlerClass()
	{
		StashHandlers::getHandlerClass('Array');

		$this->assertEquals('StashArray', StashHandlers::getHandlerClass('Array'),
							'getHandlerClass returns proper classname for Array handler');
	}

}

?>