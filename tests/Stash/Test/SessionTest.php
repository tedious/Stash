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

use Stash\Session;
use Stash\Pool;
use Stash\Handler\Ephemeral;

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterHandler()
    {

    }

    public function testReadAndWrite()
    {
        $session = new Session(new Pool());

        $this->assertSame('', $session->read('session_id'),
                          'Empty session returns empty string.');

        $this->assertTrue($session->write('session_id', 'session_data'),
                          'Data was written to the session.');
        $this->assertSame('session_data', $session->read('session_id'),
                          'Active session returns session data.');
    }

    public function testOpen()
    {
        $pool = new Pool();

        $sessionA = new Session($pool);
        $sessionA->open('first', 'session');
        $sessionA->write('shared_id', "session_a_data");

        $sessionB = new Session($pool);
        $sessionB->open('second', 'session');
        $sessionB->write('shared_id', "session_b_data");

        $DataA = $sessionA->read('shared_id');
        $DataB = $sessionB->read('shared_id');

        $this->assertTrue($DataA != $DataB,
                          'Sessions with different paths do not share data.');


        $pool = new Pool();

        $sessionA = new Session($pool);
        $sessionA->open('shared_path', 'sessionA');
        $sessionA->write('shared_id', "session_a_data");

        $sessionB = new Session($pool);
        $sessionB->open('shared_path', 'sessionB');
        $sessionB->write('shared_id', "session_b_data");

        $DataA = $sessionA->read('shared_id');
        $DataB = $sessionB->read('shared_id');

        $this->assertTrue($DataA != $DataB,
                          'Sessions with different names do not share data.');
    }


    public function testClose()
    {
        $session = new Session(new Pool());
        $this->assertTrue($session->close(),
                          'Session was closed');
    }

    public function testDestroy()
    {
        $session = new Session(new Pool());

        $session->write('session_id', 'session_data');
        $session->write('session_id', 'session_data');
        $this->assertSame('session_data', $session->read('session_id'),
                          'Active session returns session data.');

        $this->assertTrue($session->destroy('session_id'),
                          'Data was removed from the session.');

        $this->assertSame('', $session->read('session_id'),
                          'Destroyed session returns empty string.');
    }

    public function testGarbageCollect()
    {

    }

}