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

use Stash\Test\Stubs\PoolGetDriverStub;
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
    protected $extension = '.php';
    protected $persistence = true;

    protected function getOptions($options = array())
    {
        return array_merge(array('memKeyLimit' => 2), $options);
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testOptionKeyHashFunctionException()
    {
        $driver = new FileSystem($this->getOptions(array('keyHashFunction' => 'foobar_'.mt_rand())));
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testOptionEncoderObjectException()
    {
        $encoder = new \stdClass();
        $driver = new FileSystem($this->getOptions(array('encoder' => $encoder)));
    }

    /**
     * @expectedException Stash\Exception\RuntimeException
     */
    public function testOptionEncoderStringException()
    {
        $encoder = 'stdClass';
        $driver = new FileSystem($this->getOptions(array('encoder' => $encoder)));
    }

    public function testOptionEncoderAsObject()
    {
        $encoder = new \Stash\Driver\FileSystem\NativeEncoder();
        $driver = new FileSystem($this->getOptions(array('encoder' => $encoder)));
    }

    public function testOptionEncoderAsString()
    {
        $encoder = '\Stash\Driver\FileSystem\NativeEncoder';
        $driver = new FileSystem($this->getOptions(array('encoder' => $encoder)));
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
        $hashfunctions = array('Stash\Test\Driver\strdup', 'strrev', 'md5', function ($value) {
            return abs(crc32($value));
        });
        $paths = array('one', 'two', 'three', 'four');

        foreach ($hashfunctions as $hashfunction) {
            $driver = new FileSystem($this->getOptions(array(
                'keyHashFunction' => $hashfunction,
                'path' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash',
                'dirSplit' => 1
            )));

            $rand = str_repeat(uniqid(), 32);

            $item = new Item();

            $poolStub = new PoolGetDriverStub();
            $poolStub->setDriver($driver);
            $item->setPool($poolStub);
            $item->setKey($paths);
            $item->set($rand)->save();

            $allpaths = array_merge(array('cache'), $paths);
            $predicted = sys_get_temp_dir().
                            DIRECTORY_SEPARATOR.
                            'stash'.
                            DIRECTORY_SEPARATOR.
                            implode(DIRECTORY_SEPARATOR, array_map($hashfunction, $allpaths)).
                            $this->extension;

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
        if (strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
            $this->markTestSkipped('This test can only occur on Windows based systems.');
        }

        $cachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash';

        $driver = new FileSystem($this->getOptions(array(
            'keyHashFunction' => 'Stash\Test\Driver\strdup',
            'path' => $cachePath,
            'dirSplit' => 1
        )));
        $key=array();

        // MAX_PATH is 260 - http://msdn.microsoft.com/en-us/library/aa365247(VS.85).aspx
        while (strlen($cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $key)) < 259) {
            // 32 character string typical of an md5 sum
            $key[]="abcdefghijklmnopqrstuvwxyz123456";
        }
        $key[]="abcdefghijklmnopqrstuvwxyz123456";
        $this->expiration = time() + 3600;

        $this->setExpectedException('\Stash\Exception\WindowsPathMaxLengthException');
        $driver->storeData($key, "test", $this->expiration);
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
        if (strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
            $this->markTestSkipped('This test can only occur on Windows based systems.');
        }

        $cachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stash';

        $driver = new FileSystem($this->getOptions(array(
            'keyHashFunction' => 'Stash\Test\Driver\strdup',
            'path' => $cachePath,
            'dirSplit' => 1
        )));
        $key=array();

        // MAX_PATH is 260 - http://msdn.microsoft.com/en-us/library/aa365247(VS.85).aspx
        while (strlen($cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $key)) < 259) {
            // 32 character string typical of an md5 sum
            $key[]="abcdefghijklmnopqrstuvwxyz123456";
        }
        $this->expiration = time() + 3600;

        $this->setExpectedException('\Stash\Exception\WindowsPathMaxLengthException');
        $driver->storeData($key, "test", $this->expiration);
    }
}
