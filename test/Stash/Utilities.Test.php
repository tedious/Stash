<?php

class StashUtilitiesTest extends PHPUnit_Framework_TestCase
{
	public function testEncoding()
	{
		$this->assertEquals(Stash\Utilities::encoding(true), 'bool', 'encoding recognized \'true\' boolean.');
		$this->assertEquals(Stash\Utilities::encoding(false), 'bool', 'encoding recognized \'false\' boolean');


		$this->assertEquals(Stash\Utilities::encoding('String of doom!'), 'string', 'encoding recognized string scalar');

		$this->assertEquals(Stash\Utilities::encoding(234), 'none', 'encoding recognized integer scalar');
		$this->assertEquals(Stash\Utilities::encoding(1.432), 'none', 'encoding recognized float scalar');

		$this->assertEquals(Stash\Utilities::encoding(pow(2, 31)), 'serialize', 'encoding recognized large number');
		$this->assertEquals(Stash\Utilities::encoding(pow(2, 31)-1), 'none', 'encoding recognized small number');

		$std = new stdClass();
		$this->assertEquals(Stash\Utilities::encoding($std), 'serialize', 'encoding recognized object');

		$array = array(4, 5, 7);
		$this->assertEquals(Stash\Utilities::encoding($array), 'serialize', 'encoding recognized array');

	}

	public function testEncode()
	{
		$this->assertEquals(Stash\Utilities::encode(true), 'true', 'encode returned \'true\' as string.');
		$this->assertEquals(Stash\Utilities::encode(false), 'false', 'encode returned \'false\' as string');


		$this->assertEquals(Stash\Utilities::encode('String of doom!'), 'String of doom!',
							'encode returned string scalar');
		$this->assertEquals(Stash\Utilities::encode(234), 234,
							'encode returned integer scalar');
		$this->assertEquals(Stash\Utilities::encode(1.432), 1.432,
							'encode returned float scalar');

		$std = new stdClass();
		$this->assertEquals(Stash\Utilities::encode($std), serialize($std), 'encode serialized object');

		$array = array(4, 5, 7);
		$this->assertEquals(Stash\Utilities::encode($array), serialize($array), 'encode serialized array');
	}

	public function testDecode()
	{
		$this->assertTrue(Stash\Utilities::decode(Stash\Utilities::encode(true), Stash\Utilities::encoding(true)),
							'decode unpacked boolean \'true\'.');

		$this->assertFalse(Stash\Utilities::decode(Stash\Utilities::encode(false), Stash\Utilities::encoding(false)),
							'decode unpacked boolean \'false\'');

		$string = 'String of doom!';
		$this->assertEquals(Stash\Utilities::decode(Stash\Utilities::encode($string), Stash\Utilities::encoding($string)),
							$string, 'decode unpacked string');

		$this->assertEquals(Stash\Utilities::decode(Stash\Utilities::encode(234), Stash\Utilities::encoding(234)),
							234, 'Sdecode unpacked integer');

		$this->assertEquals(Stash\Utilities::decode(Stash\Utilities::encode(1.432), Stash\Utilities::encoding(1.432)),
							1.432, 'decode unpacked float');

		$std = new stdClass();
		$this->assertEquals(Stash\Utilities::decode(Stash\Utilities::encode($std), Stash\Utilities::encoding($std)),
							$std, 'decode unpacked object');

		$array = array(4, 5, 7);
		$this->assertEquals(Stash\Utilities::decode(Stash\Utilities::encode($array), Stash\Utilities::encoding($array)),
							$array, 'decode unpacked array');
	}

	public function testGetBaseDirectory()
	{
		$filesystem = new Stash\Handlers\FileSystem();
		$tmp = sys_get_temp_dir();
		$directory = Stash\Utilities::getBaseDirectory($filesystem);
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

		$this->assertTrue(Stash\Utilities::deleteRecursive($dirTwo . '/test3'),
						  'deleteRecursive returned true when removing single file.');
		$this->assertFileNotExists($dirTwo . '/test3', 'deleteRecursive removed single file');


		$this->assertTrue(Stash\Utilities::deleteRecursive($tmp),
						  'deleteRecursive returned true when removing directories.');
		$this->assertFileNotExists($tmp, 'deleteRecursive cleared out the directory');

		$this->assertFalse(Stash\Utilities::deleteRecursive($tmp),
						  'deleteRecursive returned false when passed nonexistant directory');
	}
}

?>