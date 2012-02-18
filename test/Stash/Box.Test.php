<?php

class StashBoxTest extends PHPUnit_Framework_TestCase
{
	protected $data = array(array('test', 'test'));

	public function testSetHandler()
	{
		Stash\Box::setHandler(new Stash\Handlers\Ephemeral(array()));
		$stash = Stash\Box::getCache();
		$this->assertAttributeInstanceOf('Stash\Handlers\Ephemeral', 'handler', $stash, 'set handler is pushed to new stash objects');
	}

	public function testGetCache()
	{
		$stash = Stash\Box::getCache('base', 'one');
		$this->assertInstanceOf('Stash\Cache', $stash, 'getCache returns a Stash\Cache object');
		$stash->store($this->data);
		$storedData = $stash->get();
		$this->assertEquals($this->data, $storedData, 'getCache returns working Stash\Cache object');
	}

	public function testClearCache()
	{
		$stash = Stash\Box::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(Stash\Box::clearCache('base', 'one'), 'clear returns true');

		$stash = Stash\Box::getCache('base', 'one');
		$this->assertNull($stash->get(), 'clear removes item');
		$this->assertTrue($stash->isMiss(), 'clear causes cache miss');
	}

	public function testPurgeCache()
	{
		$stash = Stash\Box::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(Stash\Box::purgeCache(), 'purge returns true');

		$stash = Stash\Box::getCache('base', 'one');
		$this->assertNull($stash->get(), 'purge removes item');
		$this->assertTrue($stash->isMiss(), 'purge causes cache miss');
	}

	public function testGetCacheHandlers()
	{
		$handlers = Stash\Box::getCacheHandlers();
		$this->assertTrue(is_array($handlers), '');
	}
}

?>