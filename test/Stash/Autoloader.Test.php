<?php

class StashAutoloaderTest extends PHPUnit_Framework_TestCase
{
	protected $classes = array(
										'StashBox',
										'StashHandler',
										'StashHandlers',
										'StashManager',
										'StashError',
										'StashWarning',
										'StashUtilities',
										'Stash',
										'\Stash\Handlers\Apc',
										'\Stash\Handlers\ExceptionTest',
										'\Stash\Handlers\Xcache',
										'\Stash\Handlers\Sqlite',
										'\Stash\Handlers\FileSystem',
										'\Stash\Handlers\Memcached',
										'\Stash\Handlers\MultiHandler'
									);

	public function testInit()
	{
		StashAutoloader::init();
		$this->assertClassHasStaticAttribute('path', 'StashAutoloader', 'Path value set by init function');
	}

	public function testAutoload()
	{
		$this->assertFalse(StashAutoloader::autoload('FakeClass'), 'Autoloader does not load non-Stash code.');
		$this->assertTrue(StashAutoloader::autoload('Stash'), 'Autoloader does load Stash code.');
		$this->assertTrue(StashAutoloader::autoload('Stash'), 'Autoloader does not attempt to reload already included classes.');
	}

	public function testRegister()
	{
		StashAutoloader::register();
		$this->assertEquals('spl_autoload_call', ini_get('unserialize_callback_func'), 'Register enables spl autoload');
		$this->assertTrue(class_exists('\Stash\Handlers\Sqlite'), 'Autoloader does load Stash code.');
	}
}

?>