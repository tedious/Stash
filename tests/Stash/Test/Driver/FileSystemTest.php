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
use Stash\Item;

function strdup($str)
{
    return $str;
}

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class FileSystemTest extends AbstractDriverTest
{
    protected $driverClass = 'Stash\Driver\FileSystem';

    protected function getOptions()
    {
        return array('memKeyLimit' => 2);
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testOptionKeyHashFunctionException()
    {
        $driver = new FileSystem(array('keyHashFunction' => 'foobar_'.mt_rand()));
    }

    public function testOptionKeyHashFunction()
    {
        $driver = new FileSystem(array('keyHashFunction' => 'md5'));
    }

    /**
     * Test that the paths are created using the key hash function.
     */
    public function testOptionKeyHashFunctionDirs()
    {
        $hashfunctions = array('Stash\Test\Driver\strdup', 'strrev', 'md5');
        $paths = array('one', 'two', 'three', 'four');

        foreach ($hashfunctions as $hashfunction) {

            $driver = new FileSystem(array(
                'keyHashFunction' => $hashfunction,
                'path' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash',
                'dirSplit' => 1
            ));

            $rand = str_repeat(uniqid(), 32);

            $item = new Item($driver, $paths);
            $item->set($rand);

            $allpaths = array_merge(array('cache'), $paths);
            $predicted = sys_get_temp_dir().
                            DIRECTORY_SEPARATOR.
                            'stash'.
                            DIRECTORY_SEPARATOR.
                            implode(DIRECTORY_SEPARATOR,
                                array_map($hashfunction, $allpaths)).
                            '.php';

            $this->assertFileExists($predicted);
        }
    }

    /**
     * Test creation of directories with long paths (Windows issue)
     *
     * Regression test for https://github.com/tedivm/Stash/issues/61
     *
     * There are currently no short term plans to allow long paths in PHP windows
     * http://www.mail-archive.com/internals@lists.php.net/msg62672.html
     *
     */
    public function testLongPathFolderCreation()
    {
        if (stristr(PHP_OS,"WIN") === false) {
            $this->markTestSkipped('Driver class unsuited for current environment');
        }

        $cachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash';

        $driver = new FileSystem(array(
            'keyHashFunction' => 'Stash\Test\Driver\strdup',
            'path' => $cachePath,
            'dirSplit' => 1
        ));
        $key=array();

        // MAX_PATH is 260 - http://msdn.microsoft.com/en-us/library/aa365247(VS.85).aspx
        while (strlen($cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR,$key)) < 259) {
            // 32 character string typical of an md5 sum
            $key[]="abcdefghijklmnopqrstuvwxyz123456";
        }
        $key[]="abcdefghijklmnopqrstuvwxyz123456";
        $this->expiration = time() + 3600;

        $this->setExpectedException('\Stash\Exception\WindowsPathMaxLengthException');
        $driver->storeData($key,"test",$this->expiration);
    }

    /**
     * Test creation of file with long paths (Windows issue)
     *
     * Regression test for https://github.com/tedivm/Stash/issues/61
     *
     * There are currently no short term plans to allow long paths in PHP windows
     * http://www.mail-archive.com/internals@lists.php.net/msg62672.html
     *
     */
    public function testLongPathFileCreation()
    {
        if (stristr(PHP_OS,"WIN") === false) {
            $this->markTestSkipped('Driver class unsuited for current environment');
        }

        $cachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash';

        $driver = new FileSystem(array(
            'keyHashFunction' => 'Stash\Test\Driver\strdup',
            'path' => $cachePath,
            'dirSplit' => 1
        ));
        $key=array();

        // MAX_PATH is 260 - http://msdn.microsoft.com/en-us/library/aa365247(VS.85).aspx
        while (strlen($cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR,$key)) < 259) {
            // 32 character string typical of an md5 sum
            $key[]="abcdefghijklmnopqrstuvwxyz123456";
        }
        $this->expiration = time() + 3600;

        $this->setExpectedException('\Stash\Exception\WindowsPathMaxLengthException');
        $driver->storeData($key,"test",$this->expiration);
    }
}
