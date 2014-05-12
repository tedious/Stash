<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Driver\Sub;

/**
 * Class SqlitePDO2
 *
 * This SQLite subdriver uses PDO and SQLite2.
 *
 * @internal
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdo2 extends SqlitePdo
{
    /**
     * {@inheritdoc}
     */
    protected static $pdoDriver = 'sqlite2';
}
