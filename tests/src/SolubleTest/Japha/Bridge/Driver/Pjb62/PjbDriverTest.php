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
use Psr\Log\NullLogger;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Bridge\Driver\Pjb62\Client;
use Soluble\Japha\Bridge\Driver\Pjb62\InternalJava;
use Soluble\Japha\Bridge\Driver\Pjb62\Pjb62Driver;
use Soluble\Japha\Bridge\Driver\Pjb62\PjbProxyClient;
use Soluble\Japha\Bridge\Exception\BrokenConnectionException;
use Soluble\Japha\Bridge\Exception\ClassNotFoundException;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Bridge\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-11-13 at 10:21:03.
 */
class PjbDriverTest extends TestCase
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
    public function constructorNoLogger(): void
    {
        $driver = new Pjb62Driver([
            'servlet_address' => $this->servlet_address,
        ], $logger = null);
        $this->assertInstanceOf(NullLogger::class, $driver->getLogger());
    }

    #[Test]
    public function instanciate(): void
    {
        $driver = $this->adapter->getDriver();
        $string = $driver->instanciate('java.lang.String');
        $this->assertSame('java.lang.String', $driver->getClassName($string));
    }

    #[Test]
    public function getClient(): void
    {
        $client = $this->adapter->getDriver()->getClient();
        $this->assertInstanceOf(PjbProxyClient::class, $client);
    }

    #[Test]
    public function isIntanceOf(): void
    {
        $string = $this->adapter->java('java.lang.String', 'hello');
        $bool = $this->adapter->getDriver()->isInstanceOf($string, 'java.lang.String');
        $this->assertTrue($bool);
    }

    #[Test]
    public function isInstanceOfThrowsException1(): void
    {
        $this->expectException(ClassNotFoundException::class);
        $string = $this->adapter->java('java.lang.String', 'hello');
        $this->adapter->getDriver()->isInstanceOf($string, 'java.invalid.Str');
    }

    #[Test]
    public function isInstanceOfThrowsException2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $string = $this->adapter->java('java.lang.String', 'hello');
        $this->adapter->getDriver()->isInstanceOf($string, []);
    }

    #[Test]
    public function setFileEncoding(): void
    {
        $driver = $this->adapter->getDriver();

        $encoding = 'ASCII';
        $driver->setFileEncoding($encoding);
        $encoding = (string) $driver->getConnectionOptions()->getEncoding();
        $this->assertSame('ASCII', $encoding);

        $encoding = 'UTF-8';
        $driver->setFileEncoding($encoding);
        $encoding = (string) $driver->getConnectionOptions()->getEncoding();
        $this->assertSame('UTF-8', $encoding);
    }

    #[Test]
    public function javaContext(): void
    {
        $context = $this->adapter->getDriver()->getContext();
        $this->assertInstanceOf(JavaObject::class, $context);
        $this->assertInstanceOf(InternalJava::class, $context);

        $fqdn = $this->adapter->getClassName($context);
        $supported = [
          // Before 6.2.11 phpjavabridge version
          'servletPrevious' => 'php.java.servlet.HttpContext',
          // FROM 6.2.11 phpjavabridge version
          'servletCurrent' => 'io.soluble.pjb.servlet.HttpContext',
          'standalone' => 'php.java.bridge.http.Context',
        ];

        $this->assertContains($fqdn, $supported);
    }

    #[Test]
    public function getJavaBridgeHeader(): void
    {
        $headersToTest = [
          'HTTP_OVERRIDE_HOST' => 'cool',
          'HTTP_HEADER_HOST' => 'cool'
        ];

        $this->assertSame('cool', Pjb62Driver::getJavaBridgeHeader('OVERRIDE_HOST', $headersToTest));
        $this->assertSame('cool', Pjb62Driver::getJavaBridgeHeader('HTTP_OVERRIDE_HOST', $headersToTest));
        $this->assertSame('cool', Pjb62Driver::getJavaBridgeHeader('HTTP_HEADER_HOST', $headersToTest));
        $this->assertSame('', Pjb62Driver::getJavaBridgeHeader('NOTHING', $headersToTest));
    }

    #[Test]
    public function javaLogLevelIsPassedToClient(): void
    {
        PjbProxyClient::unregisterInstance();

        $ba = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
            'java_log_level' => 4
        ]);

        /**
         * @var PjbProxyClient
         */
        $pjbProxyClient = $ba->getDriver()->getClient();

        $this->assertSame(4, $pjbProxyClient->getOption('java_log_level'));

        $this->assertSame(4, $pjbProxyClient->getClient()->getParam(Client::PARAM_JAVA_LOG_LEVEL));
    }

    #[Test]
    public function instanciateThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $driver = $this->adapter->getDriver();
        PjbProxyClient::unregisterInstance();
        $driver->instanciate('java.lang.String');
    }

    #[Test]
    public function getContextThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $driver = $this->adapter->getDriver();
        PjbProxyClient::unregisterInstance();
        $driver->getContext();
    }

    #[Test]
    public function invokeThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $driver = $this->adapter->getDriver();
        PjbProxyClient::unregisterInstance();
        $driver->invoke(null, 'getContext');
    }

    #[Test]
    public function javaSessionThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $driver = $this->adapter->getDriver();
        PjbProxyClient::unregisterInstance();
        $driver->getJavaSession();
    }

    #[Test]
    public function getClassNameThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $driver = $this->adapter->getDriver();
        PjbProxyClient::unregisterInstance();
        $driver->getJavaSession();
    }

    #[Test]
    public function getJavaClassThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $driver = $this->adapter->getDriver();
        PjbProxyClient::unregisterInstance();
        $driver->getJavaClass('java.lang.String');
    }
}
