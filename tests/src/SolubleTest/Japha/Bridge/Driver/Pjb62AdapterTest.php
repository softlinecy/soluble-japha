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
use Soluble\Japha\Bridge\Driver\Pjb62\Pjb62Driver;
use Soluble\Japha\Bridge\Exception\ClassNotFoundException;
use Soluble\Japha\Bridge\Exception\NoSuchMethodException;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Bridge\Driver\Pjb62\Java;
use Soluble\Japha\Bridge\Driver\Pjb62\JavaClass;
use Soluble\Japha\Bridge\Driver\Pjb62\InternalJava;
use Soluble\Japha\Bridge\Driver\Pjb62\ObjectIterator;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Bridge\Exception\NoSuchFieldException;
use PHPUnit\Framework\TestCase;

class Pjb62AdapterTest extends TestCase
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
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
        $this->adapter = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ]);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    #[Test]
    public function getDriver(): void
    {
        $driver = $this->adapter->getDriver();
        $this->assertInstanceOf(Pjb62Driver::class, $driver);
    }

    #[Test]
    public function javaThrowsClassNotFoundException(): void
    {
        $this->expectException(ClassNotFoundException::class);
        $this->adapter->java('java.util.String', 'Am I the only one ?');
    }

    #[Test]
    public function javaThrowsNoSuchMethodException(): void
    {
        $this->expectException(NoSuchMethodException::class);
        $string = $this->adapter->java('java.lang.String', 'Am I the only one ?');
        $string->myinvalidMethod();
    }

    #[Test]
    public function javaThrowsNoSuchFieldException(): void
    {
        $this->expectException(NoSuchFieldException::class);
        $this->adapter->java('java.lang.String')->nosuchfield = 10;
    }

    #[Test]
    public function javaStrings(): void
    {
        $ba = $this->adapter;

        // ascii
        $string = $ba->java('java.lang.String', 'Am I the only one ?');
        $this->assertInstanceOf(JavaObject::class, $string);
        $this->assertInstanceOf(Java::class, $string);
        $this->assertSame('Am I the only one ?', $string);
        $this->assertNotSame('Am I the only one', $string);

        // unicode - utf8
        $string = $ba->java('java.lang.String', '保障球迷權益');
        $this->assertInstanceOf(JavaObject::class, $string);
        $this->assertInstanceOf(Java::class, $string);
        $this->assertSame('保障球迷權益', $string);
        $this->assertNotSame('保障球迷', $string);
    }

    #[Test]
    public function javaHashMap(): void
    {
        $ba = $this->adapter;
        $hash = $ba->java('java.util.HashMap', ['my_key' => 'my_value']);
        $this->assertInstanceOf(Java::class, $hash);
        $this->assertSame('my_value', $hash->get('my_key'));
        $hash->put('new_key', 'oooo');
        $this->assertSame('oooo', $hash->get('new_key'));
        $hash->put('new_key', 'pppp');
        $this->assertSame('pppp', $hash->get('new_key'));

        $this->assertSame(4, $hash->get('new_key')->length());

        $hash->put('key', $ba->java('java.lang.String', '保障球迷權益'));
        $this->assertSame('保障球迷權益', $hash->get('key'));
        $this->assertSame(6, $hash->get('key')->length());
    }

    #[Test]
    public function javaClass(): void
    {
        $ba = $this->adapter;
        $cls = $ba->javaClass('java.lang.Class');
        $this->assertInstanceOf(JavaClass::class, $cls);
        $this->assertInstanceOf(\Soluble\Japha\Interfaces\JavaClass::class, $cls);
    }

    #[Test]
    public function javaSystemClass(): void
    {
        $ba = $this->adapter;

        $system = $ba->javaClass('java.lang.System');
        $this->assertInstanceOf(JavaClass::class, $system);
        $this->assertInstanceOf(\Soluble\Japha\Interfaces\JavaClass::class, $system);

        $properties = $system->getProperties();
        $this->assertInstanceOf(JavaObject::class, $properties);
        //self::assertIsString( $properties->__cast('string'));
        //self::assertIsString( $properties->__toString());

        $vm_name = $properties->get('java.vm.name');
        $this->assertInstanceOf(InternalJava::class, $vm_name);
    }

    #[Test]
    public function iterator(): void
    {
        $ba = $this->adapter;

        $system = $ba->javaClass('java.lang.System');
        $properties = $system->getProperties();

        foreach ($properties as $key => $value) {
            $this->assertIsString($key);
            $this->assertInstanceOf(InternalJava::class, $value);
        }

        $iterator = $properties->getIterator();
        $this->assertInstanceOf(ObjectIterator::class, $iterator);
        $this->assertInstanceOf('Iterator', $iterator);
    }
}
