<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Bridge\Driver;

use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Bridge\Driver\DriverInterface;
use PHPUnit\Framework\TestCase;

class DriverInterfaceTest extends TestCase
{
    /**
     * @var string
     */
    protected $servlet_address;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var DriverInterface
     */
    protected $driver;

    protected function setUp(): void
    {
        \SolubleTestFactories::startJavaBridgeServer();
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
        $this->adapter = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ]);
        $this->driver = $this->adapter->getDriver();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    #[Test]
    public function getClassName(): void
    {
        $javaString = $this->adapter->java('java.lang.String', 'Hello World');
        $className = $this->driver->getClassName($javaString);
        $this->assertSame('java.lang.String', $className);
    }

    #[Test]
    public function inspect(): void
    {
        $javaString = $this->adapter->java('java.lang.String', 'Hello World');
        $inspected = $this->driver->inspect($javaString);
        $this->assertIsString($inspected);
        $this->assertStringStartsWith('[class java.lang.String:', $inspected);
        $this->assertStringContainsString('Constructors:', $inspected);
        $this->assertStringContainsString('Fields:', $inspected);
        $this->assertStringContainsString('Methods:', $inspected);
        $this->assertStringContainsString('Classes:', $inspected);
    }

    #[Test]
    public function invoke(): void
    {
        $javaString = $this->adapter->java('java.lang.String', 'Hello');
        $length = $this->driver->invoke($javaString, 'length');
        $this->assertSame(5, $length);
        $this->assertEquals($javaString->length(), $length);

        // Multiple arguments
        $javaString = $this->adapter->java('java.lang.String', 'Hello World! World!');

        $indexStart = $this->driver->invoke($javaString, 'indexOf', ['World']);
        $index12 = $this->driver->invoke($javaString, 'indexOf', ['World', $fromIndex = 12]);
        $index16 = $this->driver->invoke($javaString, 'indexOf', ['World', $fromIndex = 16]);

        $this->assertSame(6, $indexStart);
        $this->assertSame(13, $index12);
        $this->assertSame(-1, $index16);
    }

    #[Test]
    public function invokeWithClass(): void
    {
        $javaClass = $this->adapter->javaClass('java.lang.System');
        $invokedVersion = $this->driver->invoke($javaClass, 'getProperty', ['java.version']);
        $javaVersion = $javaClass->getProperty('java.version');
        $this->assertSame((string) $javaVersion, (string) $invokedVersion);
    }
}
