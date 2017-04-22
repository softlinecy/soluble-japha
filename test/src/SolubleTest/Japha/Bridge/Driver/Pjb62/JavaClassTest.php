<?php

namespace SolubleTest\Japha\Bridge\Driver\Pjb62;

use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Interfaces\JavaType;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-11-13 at 10:21:03.
 */
class JavaClassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $servlet_address;

    /**
     * @var string
     */
    protected $options;

    /**
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

    public function testCacheEntry()
    {
        $cls = $this->adapter->javaClass('java.lang.String');

        $class = $cls->getClass();
        $this->assertInstanceOf(JavaType::class, $class);
        $this->assertInstanceOf(JavaObject::class, $class);

        $name = $cls->getName();
        $this->assertEquals('java.lang.String', $name);
    }
}