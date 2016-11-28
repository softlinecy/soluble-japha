<?php

namespace SolubleTest\Japha\Bridge;

use Soluble\Japha\Bridge\Adapter;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-04 at 16:47:42.
 */
class AdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var string
     */
    protected $servlet_address;

    /**
     *
     * @var Adapter
     */
    protected $adapter;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
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
    protected function tearDown()
    {
    }

    public function testGetDriver()
    {
        $driver = $this->adapter->getDriver();
        $this->assertInstanceOf('Soluble\Japha\Bridge\Driver\AbstractDriver', $driver);
    }



    public function testJavaClass()
    {
        $ba = $this->adapter;
        $cls = $ba->javaClass('java.lang.Class');
        $this->assertInstanceOf('Soluble\Japha\Interfaces\JavaClass', $cls);
    }

    public function testIsInstanceOf()
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

    public function testIsNull()
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

    public function testIsTrue()
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
        $this->assertTrue(!$ba->isTrue($v->get(3)));
        $this->assertTrue($ba->isTrue($v->get(4)));
        $this->assertTrue(!$ba->isTrue($v->get(5)));

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


    public function testGetClassName()
    {
        $javaString = $this->adapter->java('java.lang.String', 'Hello World');
        $className = $this->adapter->getClassName($javaString);
        $this->assertEquals('java.lang.String', $className);
    }



    public function testGetSystem()
    {
        $system = $this->adapter->getSystem();
        $this->assertInstanceOf('Soluble\Japha\Bridge\Adapter\System', $system);
    }
}
