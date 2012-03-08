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
class SqlitePdoSqlite2Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\Sqlite';
    protected $subHandlerClass = 'Stash\Handler\Sub\SqlitePdo2';

    protected function setUp()
    {
        $handler = '\\' . $this->handlerClass;
        $subHandler = '\\' . $this->subHandlerClass;

        if(!$handler::isAvailable() || !$subHandler::isAvailable()) {
            $this->markTestSkipped('Handler class unsuited for current environment');
            return;
        }

        parent::setUp();
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;
        $options['version'] = 2;
        return $options;
    }
}
