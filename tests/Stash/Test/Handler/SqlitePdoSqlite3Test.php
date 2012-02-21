<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Handler;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
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
