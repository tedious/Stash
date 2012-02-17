<?php

class StashManagerTest extends PHPUnit_Framework_TestCase
{
	protected $data = array(array('test', 'test'));

	public function testSetHandler()
	{
		$stash = StashManager::getCache('base');
		$this->assertInstanceOf('Stash', $stash, 'Unprimed StashManager returns memory based stash.');

		StashManager::setHandler('base', new StashArray(array()));
		$stash = StashManager::getCache('base');
		$this->assertAttributeInstanceOf('StashArray', 'handler', $stash, 'set handler is pushed to new stash objects');
	}

	public function testGetCache()
	{
		$stash = StashManager::getCache('base', 'one');
		$this->assertInstanceOf('Stash', $stash, 'getCache returns a Stash object');
		$stash->store($this->data);
		$storedData = $stash->get();
		$this->assertEquals($this->data, $storedData, 'getCache returns working Stash object');
	}

	public function testClearCache()
	{
		$stash = StashManager::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(StashManager::clearCache('base', 'one'), 'clear returns true');

		$stash = StashManager::getCache('base', 'one');
		$this->assertNull($stash->get(), 'clear removes item');
		$this->assertTrue($stash->isMiss(), 'clear causes cache miss');
	}

	public function testPurgeCache()
	{
		$stash = StashManager::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(StashManager::purgeCache('base'), 'purge returns true');

		$stash = StashManager::getCache('base', 'one');
		$this->assertNull($stash->get(), 'purge removes item');
		$this->assertTrue($stash->isMiss(), 'purge causes cache miss');
	}

	public function testGetCacheHandlers()
	{
		$handlers = StashManager::getCacheHandlers();
		$this->assertTrue(is_array($handlers), '');
	}
}

?>