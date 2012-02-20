<?php

namespace Stash\Test\Handler;

class StashFileSystemTest extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\FileSystem';

    protected function getOptions()
    {
        return array('memKeyLimit' => 2);
    }
}
