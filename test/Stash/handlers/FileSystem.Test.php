<?php

class StashFileSystemTest extends StashHandlerTest
{
	protected $handlerClass = 'StashFileSystem';

	protected function getOptions()
	{
		return array('memKeyLimit' => 2);
	}
}

?>