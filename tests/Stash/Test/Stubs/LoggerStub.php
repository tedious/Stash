<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stash\Test\Stubs;

use Stash;

/**
 * LoggerStub is used to test that logging events are being triggered when they should be.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @codeCoverageIgnore
 */
class LoggerStub
{
    public $lastContext;
    public $lastLevel;
    public $lastMessage;

    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->lastLevel = $level;
        $this->lastMessage = $message;
        $this->lastContext = $context;
    }
}
