<?php

namespace Stash\Test\Handler;

class SqliteSqlite2Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\Sqlite';

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'sqlite';
        $options['nesting'] = 2;
        return $options;
    }
}
