<?php

namespace Stash\Test\Handlers;

class SqlitePdoSqlite2Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handlers\Sqlite';

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;
        $options['version'] = 2;
        return $options;
    }
}
