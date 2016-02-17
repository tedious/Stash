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

use Stash\Driver\FileSystem;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class FileSystemSerializerTest extends FileSystemTest
{
    protected $driverClass = 'Stash\Driver\FileSystem';
    protected $extension = '.pser';

    protected function getOptions($options = array())
    {
        return array_merge(array('memKeyLimit' => 2, 'encoder' => 'Serializer'), $options);
    }
}
