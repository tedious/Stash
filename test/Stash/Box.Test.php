<?php

class StashBoxTest extends PHPUnit_Framework_TestCase
{
	protected $data = array(array('test', 'test'));

	public function testSetHandler()
	{
		StashBox::setHandler(new StashArray(array()));
		$stash = StashBox::getCache();
		$this->assertAttributeInstanceOf('StashArray', 'handler', $stash, 'set handler is pushed to new stash objects');
	}

	public function testGetCache()
	{
		$stash = StashBox::getCache('base', 'one');
		$this->assertInstanceOf('Stash', $stash, 'getCache returns a Stash object');
		$stash->store($this->data);
		$storedData = $stash->get();
		$this->assertEquals($this->data, $storedData, 'getCache returns working Stash object');
	}

	public function testClearCache()
	{
		$stash = StashBox::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(StashBox::clearCache('base', 'one'), 'clear returns true');

		$stash = StashBox::getCache('base', 'one');
		$this->assertNull($stash->get(), 'clear removes item');
		$this->assertTrue($stash->isMiss(), 'clear causes cache miss');
	}

	public function testPurgeCache()
	{
		$stash = StashBox::getCache('base', 'one');
		$stash->store($this->data, -600);
		$this->assertTrue(StashBox::purgeCache(), 'purge returns true');

		$stash = StashBox::getCache('base', 'one');
		$this->assertNull($stash->get(), 'purge removes item');
		$this->assertTrue($stash->isMiss(), 'purge causes cache miss');
	}

	public function testGetCacheHandlers()
	{
		$handlers = StashBox::getCacheHandlers();
		$this->assertTrue(is_array($handlers), '');
	}
}

?>