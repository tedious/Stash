<?php

namespace Stash\Test\Handlers;

class SqlitePdoSqlite3Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handlers\Sqlite';

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;
        return $options;
    }
}
