<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Driver;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdoSqlite3Test extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Sqlite';
    protected $subDriverClass = 'Stash\Driver\Sub\SqlitePdo';

    protected function setUp()
    {
        $driver = '\\' . $this->driverClass;
        $subDriver = '\\' . $this->subDriverClass;

        if (!$driver::isAvailable() || !$subDriver::isAvailable()) {
            $this->markTestSkipped('Driver class unsuited for current environment');

            return;
        }

        parent::setUp();
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;

        return $options;
    }
}
