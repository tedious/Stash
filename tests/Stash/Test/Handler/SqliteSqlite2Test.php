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
class SqliteSqlite2Test extends AbstractHandlerTest
{
    protected $handlerClass = 'Stash\Handler\Sqlite';

    protected function setUp()
    {
        $handler = '\\' . $this->handlerClass;
        if(!$handler::isAvailable()) {
            $this->markTestSkipped('Handler class unsuited for current environment');
        }

        parent::setUp();
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'sqlite';
        $options['nesting'] = 2;
        return $options;
    }
}
