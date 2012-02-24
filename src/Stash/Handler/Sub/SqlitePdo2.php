<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Handler\Sub;

use Stash\Exception\SqliteException;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdo2 extends SqlitePdo
{
    public function isAvailable()
    {
        return in_array('sqlite2', $this->getDrivers());
    }

    protected function buildHandler()
    {
        $db = new \PDO('sqlite2:' . $this->path);
        return $db;
    }
}
