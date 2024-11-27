<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Bridge\Driver\Pjb62;

use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Bridge\Driver\Pjb62\AbstractJava;
use Soluble\Japha\Bridge\Driver\Pjb62\InternalJava;
use Soluble\Japha\Bridge\Exception\NoSuchFieldException;
use Soluble\Japha\Bridge\Exception\NoSuchMethodException;
use Soluble\Japha\Interfaces\JavaObject;
use PHPUnit\Framework\TestCase;

class AbstractJavaTest extends TestCase
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
    public function arrayAccessOffsetExists(): void
    {
        $ba = $this->adapter;
        $arrayList = $ba->java('java.util.ArrayList');
        $this->assertInstanceOf(AbstractJava::class, $arrayList);

        $this->assertArrayNotHasKey(0, $arrayList);
        $arrayList->add('Hello');
        $this->assertArrayHasKey(0, $arrayList);

        $hashMap = $ba->java('java.util.HashMap');
        $this->assertInstanceOf(AbstractJava::class, $hashMap);

        $this->assertArrayNotHasKey('key', $hashMap);
        $hashMap->put('key', 'value');
        $this->assertArrayHasKey('key', $hashMap);
    }

    #[Test]
    public function customOffsetExists(): void
    {
        $ba = $this->adapter;

        $hashMap = $ba->java('java.util.HashMap');

        $hashMap->put('key', 'value');

        try {
            // We bypass the regular php method offsetExists
            // because we add more parameters.
            // so the method will be called on HashMap
            // HashMap.offsetExists('key', 'param1', 'param2'
            // and does not exists
            $hashMap->offsetExists('key', 'param1', 'param2');
            $this->assertTrue(false, 'Method should not exists on HashMap');
        } catch (NoSuchMethodException) {
            $this->assertTrue(true, 'Method does not exists as expected');
        }
    }

    #[Test]
    public function arrayAccessOffsetGet(): void
    {
        $ba = $this->adapter;

        $hashMap = $ba->java('java.util.HashMap');

        $hashMap->put('key', 'value');

        $this->assertSame('value', $hashMap['key']);

        try {
            $hashMap->offsetGet('key', 'param1', 'param2');
            $this->assertTrue(false, 'Method should not exists on HashMap');
        } catch (NoSuchMethodException) {
            $this->assertTrue(true, 'Method does not exists as expected');
        }
    }

    #[Test]
    public function arrayAccessOffsetSet(): void
    {
        $ba = $this->adapter;

        $hashMap = $ba->java('java.util.HashMap');

        $hashMap['key'] = 'value';
        $this->assertSame('value', $hashMap['key']);

        try {
            $hashMap->offsetSet('key', 'param1', 'param2');
            $this->assertTrue(false, 'Method should not exists on HashMap');
        } catch (NoSuchMethodException) {
            $this->assertTrue(true, 'Method does not exists as expected');
        }
    }

    #[Test]
    public function arrayAccessOffsetUnset(): void
    {
        $ba = $this->adapter;

        $hashMap = $ba->java('java.util.HashMap');

        $hashMap['key'] = 'value';
        $this->assertSame('value', $hashMap['key']);

        unset($hashMap['key']);
        $this->assertArrayNotHasKey('key', $hashMap);

        try {
            $hashMap->offsetUnset('key', 'param1', 'param2');
            $this->assertTrue(false, 'Method should not exists on HashMap');
        } catch (NoSuchMethodException) {
            $this->assertTrue(true, 'Method does not exists as expected');
        }
    }

    #[Test]
    public function getIterator(): void
    {
        $ba = $this->adapter;

        $hashMap = $ba->java('java.util.HashMap');

        $hashMap['key'] = 'value';
        foreach ($hashMap as $key => $value) {
            $this->assertSame('key', $key);
            $this->assertSame('value', $value);
        }

        try {
            $hashMap->getIterator('key', 'param1', 'param2');
            $this->assertTrue(false, 'Method should not exists on HashMap');
        } catch (NoSuchMethodException) {
            $this->assertTrue(true, 'Method does not exists as expected');
        }
    }

    #[Test]
    public function magicSet(): void
    {
        $ba = $this->adapter;
        $hashMap = $ba->java('java.util.HashMap');
        try {
            $hashMap->aProperty = 'cool';
            $this->assertTrue(false, 'Property should not exists on HashMap');
        } catch (NoSuchFieldException) {
            $this->assertTrue(true, 'Property does not exists as expected');
        }
    }

    #[Test]
    public function getClass(): void
    {
        $ba = $this->adapter;
        $hashMap = $ba->java('java.util.HashMap');
        $c = $hashMap->getClass();
        $this->assertInstanceOf(InternalJava::class, $c);
        $this->assertInstanceOf(JavaObject::class, $c);
    }
}
