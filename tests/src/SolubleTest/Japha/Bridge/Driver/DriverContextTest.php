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
use Soluble\Japha\Bridge\Driver\ClientInterface;
use Soluble\Japha\Bridge\Driver\DriverInterface;
use Soluble\Japha\Interfaces\JavaObject;
use PHPUnit\Framework\TestCase;

class DriverContextTest extends TestCase
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
    public function getClient(): void
    {
        $client = $this->driver->getClient();
        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    #[Test]
    public function context(): void
    {
        $context = $this->driver->getContext();
        $this->assertInstanceOf(JavaObject::class, $context);

        $className = $this->driver->getClassName($context);

        $supported = [
            // Before 6.2.11 phpjavabridge version
            'php.java.servlet.HttpContext' => 'servlet',
            // From 6.2.11 phpjavabridge version
            'io.soluble.pjb.servlet.HttpContext' => 'servlet',

            // For standalone
            'io.soluble.pjb.bridge.http.Context' => 'standalone',
            'php.java.bridge.http.Context' => 'standalone',
        ];

        $this->assertContains($className, array_keys($supported));

        if ($supported[$className] === 'servlet') {
            // ## TESTING HttpServletRequest
            // Those tests does not make sense with the standalone
            $httpServletRequest = $context->getHttpServletRequest();
            $this->assertInstanceOf(JavaObject::class, $httpServletRequest);

            $this->assertContains($this->driver->getClassName($httpServletRequest), [
                'io.soluble.pjb.servlet.RemoteHttpServletRequest',
                'php.java.servlet.RemoteServletRequest',
                'php.java.servlet.RemoteHttpServletRequest' // For Pjb713
            ]);

            //echo $this->driver->inspect($httpServletRequest);

            $this->assertSame('java.util.Locale', $this->driver->getClassName($httpServletRequest->getLocale()));

            $this->assertSame('java.lang.String', $this->driver->getClassName($httpServletRequest->getMethod()));

            $this->assertSame('java.lang.String', $this->driver->getClassName($httpServletRequest->getProtocol()));

            $this->assertStringContainsString('HTTP', (string) $httpServletRequest->getProtocol());

            $requestUri = $httpServletRequest->getRequestUri();
            $this->assertSame('java.lang.String', $this->driver->getClassName($requestUri));

            $this->assertStringContainsString('.phpjavabridge', (string) $requestUri);

            $headerNames = $httpServletRequest->getHeaderNames();

            $this->assertStringContainsString('Enum', $this->driver->getClassName($headerNames));

            $headers = [];
            while ($headerNames->hasMoreElements()) {
                $name = $headerNames->nextElement();
                $value = $httpServletRequest->getHeader($name);
                $headers[(string) $name] = (string) $value;
            }

            $this->assertArrayHasKey('host', $headers); // 127.0.0.1:8080 (tomcat listening address)

            /*
            self::assertArrayHasKey('cache-control', $headers);
            self::assertArrayHasKey('transfert-encoding', $headers);
            */

            // ## TESTING HttpServletResponse

            $httpServletResponse = $context->getHttpServletResponse();

            $this->assertContains($this->driver->getClassName($httpServletResponse), [
                'io.soluble.pjb.servlet.RemoteHttpServletResponse',
                'php.java.servlet.RemoteServletResponse',
                'php.java.servlet.RemoteHttpServletResponse' // For pjb713
            ]);

            if ($this->driver->getClassName($context) == 'io.soluble.pjb.servlet.HttpContext') {
                $httpServletRequestFromAttribute = $context->getAttribute('io.soluble.pjb.servlet.HttpServletRequest');
                $this->assertSame('io.soluble.pjb.servlet.RemoteHttpServletRequest', $this->driver->getClassName($httpServletRequestFromAttribute));
            }

            // @todo future work on session (issue with session already committed, need more tests)
            //var_dump($context->getAttribute('name'));
        }
    }
}
