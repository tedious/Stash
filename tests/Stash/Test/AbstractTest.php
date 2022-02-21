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

/**
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
abstract class AbstractTest extends \PHPUnit\Framework\TestCase
{

  public function accessProtected($obj, $prop) {
    $reflection = new \ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue($obj);
  }

  public function assertAttributeEquals($expectedValue, $actualAttributeName, $object, $errorMessage="") {
    $actualValue = $this->accessProtected($object, $actualAttributeName);
    return $this->assertSame($expectedValue, $actualValue, $errorMessage);
  }

  public function assertAttributeInstanceOf($expectedClass, $actualAttributeName, $object, $errorMessage="") {
    $actualValue = $this->accessProtected($object, $actualAttributeName);
    return $this->assertInstanceOf($expectedClass, $actualValue);
  }

}
