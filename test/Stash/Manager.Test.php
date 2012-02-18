<?php

class StashManagerTest extends PHPUnit_Framework_TestCase
{
	protected $data = array(array('test', 'test'));

	public function testSetHandler()
	{
		$stash = Stash\Manager::getCache('base');
		$this->assertInstanceOf('Stash\Cache', $stash, 'Unprimed Stash\Manager returns memory based stash.');

		Stash\Manager::setHandler('base', new Stash\Handlers\Ephemeral(array()));
		$stash = Stash\Manager::getCache('base');
		$this->assertAttributeInstanceOf('Stash\Handlers\Ephemeral', 'handler', $stash, 'set handler is pushed to new stash objects');
	}

	public function testGetCache()
	{
		$stash = Stash\Manager::getCache('base', 'one');
		$this->assertInstanceOf('Stash\Cache', $stash, 'getCache returns a Stash\Cache object');
		$stash->store($this->data);
		$storedData = $stash->get();
		$this->assertEquals($this->data, $storedData, 'getCache returns working Stash object');
	}

	public function testClearCache()
	{
		$stash = Stash\Manager::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(Stash\Manager::clearCache('base', 'one'), 'clear returns true');

		$stash = Stash\Manager::getCache('base', 'one');
		$this->assertNull($stash->get(), 'clear removes item');
		$this->assertTrue($stash->isMiss(), 'clear causes cache miss');
	}

	public function testPurgeCache()
	{
		$stash = Stash\Manager::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(Stash\Manager::purgeCache('base'), 'purge returns true');

		$stash = Stash\Manager::getCache('base', 'one');
		$this->assertNull($stash->get(), 'purge removes item');
		$this->assertTrue($stash->isMiss(), 'purge causes cache miss');
	}

	public function testGetCacheHandlers()
	{
		$handlers = Stash\Manager::getCacheHandlers();
		$this->assertTrue(is_array($handlers), '');
	}
}

?>