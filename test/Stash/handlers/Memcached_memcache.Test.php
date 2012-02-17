<?php

class StashMemcached_memcacheTest extends StashHandlerTest
{
	protected $handlerClass = 'StashMemcached';
	protected $extension = 'memcache';

	protected $servers = array('127.0.0.1', '11211');

	private $setup = false;

	protected function getOptions()
	{
		return array('extension' => $this->extension,
					 'servers' => $this->servers);
	}

	protected function setUp()
	{
		if(!$this->setup)
		{
			$this->startTime = time();
			$this->expiration = $this->startTime + 3600;
			$handlerClass = $this->handlerClass;

			if(!$handlerClass::canEnable())
			{
				$this->markTestSkipped('Handler class unsuited for current environment');
				return;
			}

			if(!class_exists(ucfirst($this->extension)))
			{
				$this->markTestSkipped('Test requires ' . $this->extension . ' extension');
				return;
			}

			if(!($sock = @fsockopen($this->servers[0], $this->servers[1], $errno, $errstr, 1)))
			{
				$this->markTestSkipped('Memcache tests require memcache server');
				return;
			}

			fclose($sock);
			$this->data['object'] = new stdClass();
		}
	}

	public function testConstructionOptions()
	{
		$key = array('apple', 'sauce');

		$options = array();
		$options['servers'][] =  array('127.0.0.1', '11211', '50');
		$options['servers'][] =  array('127.0.0.1', '11211');
		$options['extension'] = $this->extension;
		$handler = new StashMemcached($options);

		$stash = new Stash($handler);
		$stash->setupKey($key);
		$this->assertTrue($stash->store($key), 'Able to load and store memcached handler using multiple servers');

		$options = array();
		$options['extension'] = $this->extension;
		$handler = new StashMemcached($options);
		$stash = new Stash($handler);
		$stash->setupKey($key);
		$this->assertTrue($stash->store($key), 'Able to load and store memcached handler using default server');
	}
}

?>