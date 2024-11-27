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
use Soluble\Japha\Bridge\Exception\JavaException;
use Soluble\Japha\Interfaces\JavaObject;
use PHPUnit\Framework\TestCase;

class DriverContextServletTest extends TestCase
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
    public function getServlet(): void
    {
        // The servlet context allows to call
        // methods present in on the servlet side
        // Check issue https://github.com/belgattitude/soluble-japha/issues/26
        // for more information

        $context = $this->driver->getContext();
        try {
            $servletContext = $context->getServlet();
        } catch (JavaException $javaException) {
            $msg = $javaException->getMessage();
            if ($javaException->getJavaClassName() === 'java.lang.IllegalStateException' &&
                preg_match('/PHP not running in a servlet environment/', $msg)) {
                // Basically mark this test as skipped as the test
                // was made on the standalone server
                $this->markTestIncomplete('Retrieval of servlet context is not supported with the standalone server');

                return;
            }
            throw $javaException;
        }

        $this->assertInstanceOf(JavaObject::class, $servletContext);

        $className = $this->driver->getClassName($servletContext);

        $supported = [
            // Before 6.2.11 phpjavabridge version
            'php.java.servlet.PhpJavaServlet',
            // From 6.2.11 phpjavabridge version
            'io.soluble.pjb.servlet.PhpJavaServlet'
        ];

        $this->assertContains($className, $supported);

        //  From javax.servlet.GenericServlet

        $servletName = $servletContext->getServletName();
        $this->assertInstanceOf(JavaObject::class, $servletName);
        $this->assertSame('java.lang.String', $this->driver->getClassName($servletName));
        $this->assertSame('phpjavaservlet', strtolower((string) $servletName));

        $servletInfo = $servletContext->getServletInfo();
        $this->assertInstanceOf(JavaObject::class, $servletInfo);
        $this->assertSame('java.lang.String', $this->driver->getClassName($servletInfo));

        $servletConfig = $servletContext->getServletConfig();
        $this->assertInstanceOf(JavaObject::class, $servletConfig);

        // on Tomcat could be : org.apache.catalina.core.StandardWrapperFacade
        //self::assertEquals('org.apache.catalina.core.StandardWrapperFacade', $this->driver->getClassName($servletConfig));

        $servletContext = $context->getServletContext();

        $paramNames = $servletContext->getInitParameterNames();
        //echo $this->driver->getClassName($paramNames);
        $this->assertInstanceOf(JavaObject::class, $paramNames);
    }

    #[Test]
    public function getServletOnTomcat(): void
    {
        $context = $this->driver->getContext();
        try {
            $servletContext = $context->getServlet();
        } catch (JavaException $javaException) {
            $msg = $javaException->getMessage();
            if ($javaException->getJavaClassName() === 'java.lang.IllegalStateException' &&
                preg_match('/PHP not running in a servlet environment/', $msg)) {
                // Basically mark this test as skipped as the test
                // was made on the standalone server
                $this->markTestIncomplete('Retrieval of servlet context is not supported with the standalone server');

                return;
            }
            throw $javaException;
        }

        $servletConfig = $servletContext->getServletConfig();
        $this->assertSame('org.apache.catalina.core.StandardWrapperFacade', $this->driver->getClassName($servletConfig));

        $this->assertSame('org.apache.catalina.core.ApplicationContextFacade', $this->driver->getClassName($context->getServletContext()));
    }
}
