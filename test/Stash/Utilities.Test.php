<?php

class StashUtilitiesTest extends PHPUnit_Framework_TestCase
{
	public function testEncoding()
	{
		$this->assertEquals(StashUtilities::encoding(true), 'bool', 'encoding recognized \'true\' boolean.');
		$this->assertEquals(StashUtilities::encoding(false), 'bool', 'encoding recognized \'false\' boolean');


		$this->assertEquals(StashUtilities::encoding('String of doom!'), 'string', 'encoding recognized string scalar');

		$this->assertEquals(StashUtilities::encoding(234), 'none', 'encoding recognized integer scalar');
		$this->assertEquals(StashUtilities::encoding(1.432), 'none', 'encoding recognized float scalar');

		$this->assertEquals(StashUtilities::encoding(pow(2, 31)), 'serialize', 'encoding recognized large number');
		$this->assertEquals(StashUtilities::encoding(pow(2, 31)-1), 'none', 'encoding recognized small number');

		$std = new stdClass();
		$this->assertEquals(StashUtilities::encoding($std), 'serialize', 'encoding recognized object');

		$array = array(4, 5, 7);
		$this->assertEquals(StashUtilities::encoding($array), 'serialize', 'encoding recognized array');

	}

	public function testEncode()
	{
		$this->assertEquals(StashUtilities::encode(true), 'true', 'encode returned \'true\' as string.');
		$this->assertEquals(StashUtilities::encode(false), 'false', 'encode returned \'false\' as string');


		$this->assertEquals(StashUtilities::encode('String of doom!'), 'String of doom!',
							'encode returned string scalar');
		$this->assertEquals(StashUtilities::encode(234), 234,
							'encode returned integer scalar');
		$this->assertEquals(StashUtilities::encode(1.432), 1.432,
							'encode returned float scalar');

		$std = new stdClass();
		$this->assertEquals(StashUtilities::encode($std), serialize($std), 'encode serialized object');

		$array = array(4, 5, 7);
		$this->assertEquals(StashUtilities::encode($array), serialize($array), 'encode serialized array');
	}

	public function testDecode()
	{
		$this->assertTrue(StashUtilities::decode(StashUtilities::encode(true), StashUtilities::encoding(true)),
							'decode unpacked boolean \'true\'.');

		$this->assertFalse(StashUtilities::decode(StashUtilities::encode(false), StashUtilities::encoding(false)),
							'decode unpacked boolean \'false\'');

		$string = 'String of doom!';
		$this->assertEquals(StashUtilities::decode(StashUtilities::encode($string), StashUtilities::encoding($string)),
							$string, 'decode unpacked string');

		$this->assertEquals(StashUtilities::decode(StashUtilities::encode(234), StashUtilities::encoding(234)),
							234, 'Sdecode unpacked integer');

		$this->assertEquals(StashUtilities::decode(StashUtilities::encode(1.432), StashUtilities::encoding(1.432)),
							1.432, 'decode unpacked float');

		$std = new stdClass();
		$this->assertEquals(StashUtilities::decode(StashUtilities::encode($std), StashUtilities::encoding($std)),
							$std, 'decode unpacked object');

		$array = array(4, 5, 7);
		$this->assertEquals(StashUtilities::decode(StashUtilities::encode($array), StashUtilities::encoding($array)),
							$array, 'decode unpacked array');
	}

	public function testGetBaseDirectory()
	{
		$filesystem = new StashFileSystem();
		$tmp = sys_get_temp_dir();
		$directory = StashUtilities::getBaseDirectory($filesystem);
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

		$this->assertTrue(StashUtilities::deleteRecursive($dirTwo . '/test3'),
						  'deleteRecursive returned true when removing single file.');
		$this->assertFileNotExists($dirTwo . '/test3', 'deleteRecursive removed single file');


		$this->assertTrue(StashUtilities::deleteRecursive($tmp),
						  'deleteRecursive returned true when removing directories.');
		$this->assertFileNotExists($tmp, 'deleteRecursive cleared out the directory');

		$this->assertFalse(StashUtilities::deleteRecursive($tmp),
						  'deleteRecursive returned false when passed nonexistant directory');
	}
}

?>