<?php

class Stash_ExceptionTest extends PHPUnit_Framework_TestCase
{
	public function testStore()
	{
		$handler = new \Stash\Handlers\ExceptionTest();
		$stash = new Stash($handler);
		$stash->setupKey('path', 'to', 'store');
		$this->assertFalse($stash->store(array(1,2,3), 3600));
	}

	public function testGet()
	{
		$stash = new Stash(new \Stash\Handlers\ExceptionTest());
		$stash->setupKey('path', 'to', 'get');
		$this->assertNull($stash->get());
	}

	public function testClear()
	{
		$stash = new Stash(new \Stash\Handlers\ExceptionTest());
		$stash->setupKey('path', 'to', 'clear');
		$this->assertFalse($stash->clear());
	}

	public function testPurge()
	{
		$stash = new Stash(new \Stash\Handlers\ExceptionTest());
		$this->assertFalse($stash->purge());
	}
}

?>