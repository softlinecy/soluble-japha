<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Bridge;

use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Bridge\Driver\AbstractDriver;
use Soluble\Japha\Interfaces\JavaClass;
use Soluble\Japha\Bridge\Adapter\System;
use Soluble\Japha\Bridge\Adapter;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-04 at 16:47:42.
 */
class AdapterTest extends TestCase
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
        \SolubleTestFactories::startJavaBridgeServer();

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
        $this->assertInstanceOf(AbstractDriver::class, $driver);
    }

    #[Test]
    public function javaClass(): void
    {
        $ba = $this->adapter;
        $cls = $ba->javaClass('java.lang.Class');
        $this->assertInstanceOf(JavaClass::class, $cls);
    }

    #[Test]
    public function values(): void
    {
        $ba = $this->adapter;

        $array = array_fill(0, 1000, 'Hello');
        $vector = $ba->java('java.util.Vector', $array);

        $ba->values($vector);
        $this->assertEquals($array, $ba->values($vector));

        $arrOfArray = [
            'real' => true,
            'what' => 'Too early to know',
            'count' => 2017,
            'arr10000' => array_fill(0, 10000, 'Hello world')
        ];

        $hashMap = $ba->java('java.util.HashMap', $arrOfArray);
        $arrFromJava = $ba->values($hashMap);

        $this->assertEquals($arrOfArray, $arrFromJava);
    }

    #[Test]
    public function isInstanceOfSuccess(string $className): void
    {
        $ba = $this->adapter;

        $system = $ba->javaClass('java.lang.System');
        $string = $ba->java('java.lang.String', 'Hello');
        $bigint = $ba->java('java.math.BigInteger', 1234567890123);
        $hash = $ba->java('java.util.HashMap', []);

        $this->assertFalse($ba->isInstanceOf($system, $string));
        $this->assertFalse($ba->isInstanceOf($hash, $string));
        $this->assertTrue($ba->isInstanceOf($string, 'java.lang.String'));
        $this->assertFalse($ba->isInstanceOf($string, 'java.util.HashMap'));
        $this->assertTrue($ba->isInstanceOf($hash, 'java.util.HashMap'));
        $this->assertTrue($ba->isInstanceOf($bigint, 'java.math.BigInteger'));
        $this->assertTrue($ba->isInstanceOf($bigint, 'java.lang.Object'));
        $this->assertTrue($ba->isInstanceOf($hash, 'java.lang.Object'));

        $this->assertFalse($ba->isInstanceOf($system, 'java.lang.System'));
    }

    #[Test]
    public function isNullSuccess(): void
    {
        $ba = $this->adapter;
        $this->assertTrue($ba->isNull(null));
        $this->assertTrue($ba->isNull());

        $system = $ba->javaClass('java.lang.System');
        $this->assertFalse($ba->isNull($system));

        $emptyString = $ba->java('java.lang.String', '');
        $this->assertFalse($ba->isNull($emptyString));

        //because in this case it's empty
        $nullString = $ba->java('java.lang.String');
        $this->assertFalse($ba->isNull($nullString));

        $v = $ba->java('java.util.Vector', [1, 2, 3]);
        $v->add(1, null);
        $v->add(2, 0);

        $this->assertTrue($ba->isNull($v->get(1)));
        $this->assertFalse($ba->isNull($v->get(2)));
    }

    #[Test]
    public function isTrueSucess(): void
    {
        $ba = $this->adapter;

        $b = $ba->java('java.lang.Boolean', true);
        $this->assertTrue($ba->isTrue($b));

        $b = $ba->java('java.lang.Boolean', false);
        $this->assertFalse($ba->isTrue($b));

        // initial capacity of 10
        $v = $ba->java('java.util.Vector', [1, 2, 3, 4, 5]);
        $this->assertFalse($ba->isTrue($v));

        $v->add(1, 1);
        $v->add(2, $ba->java('java.lang.Boolean', true));
        $v->add(3, $ba->java('java.lang.Boolean', false));
        $v->add(4, true);
        $v->add(5, false);

        $this->assertTrue($ba->isTrue($v->get(1)));
        $this->assertTrue($ba->isTrue($v->get(2)));
        $this->assertFalse($ba->isTrue($v->get(3)));
        $this->assertTrue($ba->isTrue($v->get(4)));
        $this->assertFalse($ba->isTrue($v->get(5)));

        // Empty string are considered as false
        $s = $ba->java('java.lang.String');
        $this->assertFalse($ba->isTrue($s));

        $s = $ba->java('java.lang.String', '');
        $this->assertFalse($ba->isTrue($s));

        $s = $ba->java('java.lang.String', 'true');
        $this->assertFalse($ba->isTrue($s));

        $s = $ba->java('java.lang.String', '1');
        $this->assertFalse($ba->isTrue($s));

        $this->assertTrue($ba->isTrue($ba->java('java.lang.Boolean', 1)));
        $this->assertTrue($ba->isTrue($ba->java('java.lang.Boolean', true)));

        $this->assertFalse($ba->isTrue($ba->java('java.lang.Boolean', 0)));
        $this->assertFalse($ba->isTrue($ba->java('java.lang.Boolean', false)));
    }

    #[Test]
    public function getClassName(): void
    {
        $javaString = $this->adapter->java('java.lang.String', 'Hello World');
        $className = $this->adapter->getClassName($javaString);
        $this->assertSame('java.lang.String', $className);
    }

    #[Test]
    public function getSystem(): void
    {
        $system = $this->adapter->getSystem();
        $this->assertInstanceOf(System::class, $system);
    }
}