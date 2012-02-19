<?php

namespace Stash\Test;

use Stash\Autoloader;

class AutoloaderTest extends \PHPUnit_Framework_TestCase
{
    protected $classes = array('Stash\Box',
                               'Stash\Handler',
                               'Stash\Handlers',
                               'Stash\Manager',
                               'Stash\Error',
                               'Stash\Warning',
                               'Stash\Utilities',
                               'Stash\Cache',
                               'Stash\Handler\Apc',
                               'Stash\Handler\ExceptionTest',
                               'Stash\Handler\Xcache',
                               'Stash\Handler\Sqlite',
                               'Stash\Handler\FileSystem',
                               'Stash\Handler\Memcached',
                               'Stash\Handler\MultiHandler'
    );

    public function testInit()
    {
        Autoloader::init();
        $this->assertClassHasStaticAttribute('path', 'Stash\Autoloader', 'Path value set by init function');
    }

    public function testAutoload()
    {
        $this->assertFalse(Autoloader::autoload('FakeClass'), 'Autoloader does not load non-Stash code.');
        $this->assertTrue(Autoloader::autoload('Stash\Cache'), 'Autoloader does load Cache code.');
        $this->assertTrue(Autoloader::autoload('Stash\Cache'), 'Autoloader does not attempt to reload already included classes.');
    }

    public function testRegister()
    {
        Autoloader::register();
        $this->assertEquals('spl_autoload_call', ini_get('unserialize_callback_func'), 'Register enables spl autoload');
        $this->assertTrue(class_exists('Stash\Handler\Sqlite'), 'Autoloader does load Stash code.');
    }
}
