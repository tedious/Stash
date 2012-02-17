<?php

class StashApcTest extends StashHandlerTest
{
	protected $handlerClass = 'StashApc';

	public function testConstructor()
	{
		$handlerType = $this->handlerClass;
		$options = $this->getOptions();
		$options['namespace'] = 'namespace_test';
		$options['ttl'] = 15;
		$handler = new $handlerType($options);

		$this->assertAttributeEquals('namespace_test', 'apcNamespace', $handler, 'APC is setting supplied namespace.');
		$this->assertAttributeEquals(15, 'ttl', $handler, 'APC is setting supplied ttl.');

		return parent::testConstructor();
	}


}

?>