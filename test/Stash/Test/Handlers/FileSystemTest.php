<?php

namespace Stash\Test\Handlers;

class StashFileSystemTest extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handlers\FileSystem';

    protected function getOptions()
    {
        return array('memKeyLimit' => 2);
    }
}
