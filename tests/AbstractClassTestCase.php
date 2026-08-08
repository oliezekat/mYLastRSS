<?php

namespace Oliezekat\MyLastRss\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractClassTestCase extends TestCase
{
    abstract protected function getClassName();

    abstract protected function getClassMethods();

    public function testClassInterface()
    {
        $className = $this->getClassName();
        $this->assertTrue(is_string($className), __CLASS__ . "::getClassName() return string");
        $this->assertFalse(empty(trim($className)), __CLASS__ . "::getClassName() not empty");
        $this->assertTrue(class_exists($className, false), $className . " class exists");
        $methods = $this->getClassMethods();
        $this->assertTrue(is_array($methods), __CLASS__ . "::getClassMethods() return array");
        $this->assertTrue((count($methods) !== 0), __CLASS__ . "::getClassMethods() not empty");
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($className, $method), $className . ' class has "' . $method . '()" method');
        }
    }
}
