<?php

class StashFileSystemTest extends StashHandlerTest
{
	protected $handlerClass = '\Stash\Handlers\FileSystem';

	protected function getOptions()
	{
		return array('memKeyLimit' => 2);
	}
}

?>