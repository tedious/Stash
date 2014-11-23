<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test;

use Stash\Utilities;
use Stash\Driver\FileSystem;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class UtilitiesTest extends \PHPUnit_Framework_TestCase
{
    public function testEncoding()
    {
        $this->assertEquals(Utilities::encoding(true), 'bool', 'encoding recognized \'true\' boolean.');
        $this->assertEquals(Utilities::encoding(false), 'bool', 'encoding recognized \'false\' boolean');

        $this->assertEquals(Utilities::encoding('String of doom!'), 'string', 'encoding recognized string scalar');

        $this->assertEquals(Utilities::encoding(234), 'none', 'encoding recognized integer scalar');
        $this->assertEquals(Utilities::encoding(1.432), 'none', 'encoding recognized float scalar');

        $this->assertEquals(Utilities::encoding(pow(2, 31)), 'serialize', 'encoding recognized large number');
        $this->assertEquals(Utilities::encoding(pow(2, 31) - 1), 'none', 'encoding recognized small number');

        $std = new \stdClass();
        $this->assertEquals(Utilities::encoding($std), 'serialize', 'encoding recognized object');

        $array = array(4, 5, 7);
        $this->assertEquals(Utilities::encoding($array), 'serialize', 'encoding recognized array');
    }

    public function testEncode()
    {
        $this->assertEquals(Utilities::encode(true), 'true', 'encode returned \'true\' as string.');
        $this->assertEquals(Utilities::encode(false), 'false', 'encode returned \'false\' as string');

        $this->assertEquals(Utilities::encode('String of doom!'), 'String of doom!', 'encode returned string scalar');
        $this->assertEquals(Utilities::encode(234), 234, 'encode returned integer scalar');
        $this->assertEquals(Utilities::encode(1.432), 1.432, 'encode returned float scalar');

        $std = new \stdClass();
        $this->assertEquals(Utilities::encode($std), serialize($std), 'encode serialized object');

        $array = array(4, 5, 7);
        $this->assertEquals(Utilities::encode($array), serialize($array), 'encode serialized array');
    }

    public function testDecode()
    {
        $this->assertTrue(Utilities::decode(Utilities::encode(true), Utilities::encoding(true)), 'decode unpacked boolean \'true\'.');

        $this->assertFalse(Utilities::decode(Utilities::encode(false), Utilities::encoding(false)), 'decode unpacked boolean \'false\'');

        $string = 'String of doom!';
        $this->assertEquals(Utilities::decode(Utilities::encode($string), Utilities::encoding($string)), $string, 'decode unpacked string');

        $this->assertEquals(Utilities::decode(Utilities::encode(234), Utilities::encoding(234)), 234, 'Sdecode unpacked integer');

        $this->assertEquals(Utilities::decode(Utilities::encode(1.432), Utilities::encoding(1.432)), 1.432, 'decode unpacked float');

        $std = new \stdClass();
        $this->assertEquals(Utilities::decode(Utilities::encode($std), Utilities::encoding($std)), $std, 'decode unpacked object');

        $array = array(4, 5, 7);
        $this->assertEquals(Utilities::decode(Utilities::encode($array), Utilities::encoding($array)), $array, 'decode unpacked array');
    }

    public function testGetBaseDirectory()
    {
        $filesystem = new FileSystem();
        $tmp = sys_get_temp_dir();
        $directory = Utilities::getBaseDirectory($filesystem);
        $this->assertStringStartsWith($tmp, $directory, 'Base directory is placed inside the system temp directory.');
        $this->assertTrue(is_dir($directory), 'Base Directory exists and is a directory');
        $this->assertTrue(touch($directory . 'test'), 'Base Directory is writeable.');
    }

    public function testDeleteRecursive()
    {
        $tmp = sys_get_temp_dir() . '/stash/';
        $dirOne = $tmp . 'test/delete/recursive';
        @mkdir($dirOne, 0770, true);
        touch($dirOne . '/test');
        touch($dirOne . '/test2');

        $dirTwo = $tmp . 'recursive/delete/test';
        @mkdir($dirTwo, 0770, true);
        touch($dirTwo . '/test3');
        touch($dirTwo . '/test4');

        $this->assertTrue(Utilities::deleteRecursive($dirTwo . '/test3'), 'deleteRecursive returned true when removing single file.');
        $this->assertFileNotExists($dirTwo . '/test3', 'deleteRecursive removed single file');

        $this->assertTrue(Utilities::deleteRecursive($tmp), 'deleteRecursive returned true when removing directories.');
        $this->assertFileNotExists($tmp, 'deleteRecursive cleared out the directory');

        $this->assertFalse(Utilities::deleteRecursive($tmp), 'deleteRecursive returned false when passed nonexistant directory');

        $tmp = sys_get_temp_dir() . '/stash/test/';
        $dirOne = $tmp . '/Test1';
        @mkdir($dirOne, 0770, true);
        $dirTwo = $tmp . '/Test2';
        @mkdir($dirTwo, 0770, true);

        Utilities::deleteRecursive($dirOne, true);
        $this->assertFileExists($dirTwo, 'deleteRecursive does not erase sibling directories.');

        Utilities::deleteRecursive($dirTwo, true);
        $this->assertFileNotExists($tmp, 'deleteRecursive cleared out the empty parent directory');
    }

    public function testCheckEmptyDirectory()
    {
        $tmp = sys_get_temp_dir() . '/stash/';
        $dir2 = $tmp . 'emptytest/';
        @mkdir($dir2, 0770, true);

        $this->assertTrue(Utilities::checkForEmptyDirectory($dir2), 'Returns true for empty directories');
        $this->assertFalse(Utilities::checkForEmptyDirectory($tmp), 'Returns false for non-empty directories');
        Utilities::deleteRecursive($tmp);
    }
}
