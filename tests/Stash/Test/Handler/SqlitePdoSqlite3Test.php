<?php

namespace Stash\Test\Handler;

class SqlitePdoSqlite3Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\Sqlite';

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;
        return $options;
    }
}
