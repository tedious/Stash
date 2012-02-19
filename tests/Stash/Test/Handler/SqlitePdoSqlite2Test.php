<?php

namespace Stash\Test\Handler;

class SqlitePdoSqlite2Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\Sqlite';

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;
        $options['version'] = 2;
        return $options;
    }
}
