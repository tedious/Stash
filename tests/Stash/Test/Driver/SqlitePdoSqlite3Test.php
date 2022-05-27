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

use Stash\Utilities;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SqlitePdoSqlite3Test extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\Sqlite';
    protected $subDriverClass = 'Stash\Driver\Sub\SqlitePdo';
    protected $persistence = true;

    protected function setUp() : void
    {
        $driver = '\\' . $this->driverClass;
        $subDriver = '\\' . $this->subDriverClass;

        if (!$driver::isAvailable() || !$subDriver::isAvailable()) {
            $this->markTestSkipped('Driver class unsuited for current environment');

            return;
        }

        parent::setUp();
    }

    public function testFilePermissions()
    {
        $key = array('apple', 'sauce');

        $driverClass = '\\' . $this->driverClass;
        foreach (array(0666, 0622, 0604) as $perms) {
            $driver = new $driverClass(array('filePermissions' => $perms, 'dirPermissions' => 0777)); // constructor first
            $filename = rtrim(Utilities::getBaseDirectory($driver), '\\/') . DIRECTORY_SEPARATOR . 'cache.sqlite';
            if (file_exists($filename)) {
                unlink($filename);
            }
            $this->assertTrue($driver->storeData($key, 'test', time() + 30));
            $this->assertFileExists($filename);
            $result = fileperms($filename) & 0777; // only care for rwx
            $this->assertSame($perms, $result, sprintf('Able to set file permissions to 0%o.', $perms));
        }
        @unlink($filename);
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['extension'] = 'pdo';
        $options['nesting'] = 2;

        return $options;
    }
}
