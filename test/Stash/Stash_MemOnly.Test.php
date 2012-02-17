<?php

class Stash_MemOnlyTest extends StashTest
{

	public function testConstruct()
	{
		$stash = new Stash(null, '_memTest');
		$this->assertTrue(is_a($stash, 'Stash'), 'Test object is an instance of Stash');
		return $stash;
	}

	public function testInvalidation()
	{
		// the request only version does not have stampede protection, since it can only do one thing at a time anyways
	}

}

?>