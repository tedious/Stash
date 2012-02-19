<?php

namespace Stash\Test\Handlers;

class SqliteSqlite2Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handlers\Sqlite';

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'sqlite';
        $options['nesting'] = 2;
        return $options;
    }
}
