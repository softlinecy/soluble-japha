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
use Soluble\Japha\Bridge\Driver\Pjb62\Client;
use Soluble\Japha\Interfaces\JavaClass;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Bridge\Driver\Pjb62\Exception\InternalException;
use Soluble\Japha\Bridge\Driver\Pjb62\ParserFactory;
use Soluble\Japha\Bridge\Driver\Pjb62\PjbProxyClient;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Bridge\Driver\Pjb62\Java;
use Soluble\Japha\Bridge\Exception\BrokenConnectionException;
use Soluble\Japha\Bridge\Exception\InvalidArgumentException;
use Soluble\Japha\Bridge\Exception\InvalidUsageException;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-11-13 at 10:21:03.
 */
class PjbProxyClientTest extends TestCase
{
    /**
     * @var string
     */
    protected $servlet_address;

    /**
     * @var array
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
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
        $this->options = [
            'servlet_address' => $this->servlet_address,
            'java_prefer_values' => true,
        ];
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->clearPjbProxyClientSingleton();
    }

    #[Test]
    public function getInstance(): void
    {
        $pjbProxyClient = PjbProxyClient::getInstance($this->options);

        $this->assertInstanceOf(PjbProxyClient::class, $pjbProxyClient);
        $this->assertTrue(PjbProxyClient::isInitialized());
        $this->assertInstanceOf(Client::class, $pjbProxyClient->getClient());

        $pjbProxyClient->unregisterInstance();
        $this->assertFalse(PjbProxyClient::isInitialized());
        $this->assertInstanceOf(PjbProxyClient::class, $pjbProxyClient);
    }

    #[Test]
    public function getInstanceThrowsInvalidUsageException(): void
    {
        $this->expectException(InvalidUsageException::class);
        $pjbProxyClient = PjbProxyClient::getInstance($this->options);

        $this->assertInstanceOf(PjbProxyClient::class, $pjbProxyClient);
        $this->assertTrue(PjbProxyClient::isInitialized());
        $this->assertInstanceOf(Client::class, $pjbProxyClient->getClient());

        $pjbProxyClient->unregisterInstance();
        $this->assertFalse(PjbProxyClient::isInitialized());

        PjbProxyClient::getInstance();
    }

    #[Test]
    public function getClientThrowsBrokenConnectionException(): void
    {
        $this->expectException(BrokenConnectionException::class);
        $pjbProxyClient = PjbProxyClient::getInstance($this->options);

        $this->assertInstanceOf(PjbProxyClient::class, $pjbProxyClient);
        $this->assertTrue(PjbProxyClient::isInitialized());
        $this->assertInstanceOf(Client::class, $pjbProxyClient->getClient());

        $pjbProxyClient->unregisterInstance();
        $this->assertFalse(PjbProxyClient::isInitialized());

        PjbProxyClient::getClient();
    }

    #[Test]
    public function getJavaClass(): void
    {
        $pjbProxyClient = PjbProxyClient::getInstance($this->options);
        $cls = $pjbProxyClient->getJavaClass('java.lang.Class');
        $this->assertInstanceOf(JavaClass::class, $cls);
    }

    #[Test]
    public function invokeMethod(): void
    {
        $pjbProxyClient = PjbProxyClient::getInstance($this->options);
        $bigint1 = new Java('java.math.BigInteger', 10);
        $value = $pjbProxyClient->invokeMethod('intValue', $bigint1);
        $this->assertSame(10, $value);

        $bigint2 = new Java('java.math.BigInteger', 20);
        $bigint3 = $pjbProxyClient->invokeMethod('add', $bigint1, [$bigint2]);
        $this->assertSame(30, $bigint3->intValue());
    }

    #[Test]
    public function getClearLastException(): void
    {
        $pjbProxyClient = PjbProxyClient::getInstance($this->options);

        try {
            $pjbProxyClient->getJavaClass('ThisClassWillThrowException');
        } catch (\Exception) {
            // Do nothing
        }

        $e = $pjbProxyClient->getLastException();
        $this->assertInstanceOf(InternalException::class, $e);
        $pjbProxyClient->clearLastException();

        $e = $pjbProxyClient->getLastException();
        $this->assertNull($e);
    }

    #[Test]
    public function getOptions(): void
    {
        $options = PjbProxyClient::getInstance($this->options)->getOptions();
        $this->assertEquals($this->options['servlet_address'], $options['servlet_address']);
    }

    #[Test]
    public function getCompatibilityOption(): void
    {
        $option = PjbProxyClient::getInstance($this->options)->getCompatibilityOption();
        $this->assertSame('B', $option);
    }

    #[Test]
    public function getOptionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PjbProxyClient::getInstance($this->options)->getOption('DOESNOTEXISTS');
    }

    #[Test]
    public function forceSimpleParser(): void
    {
        // Should create a NativeParser by default
        $defaultClient = PjbProxyClient::getInstance($this->options)::getClient();
        $this->assertSame(ParserFactory::PARSER_NATIVE, $defaultClient->RUNTIME['PARSER']);

        // Recreate singleton, this time forcing the simple parser
        $this->clearPjbProxyClientSingleton();

        $proxyClient = PjbProxyClient::getInstance(array_merge(
            $this->options,
            [
                'force_simple_xml_parser' => true
            ]
        ));
        $client = $proxyClient::getClient();
        $this->assertSame(ParserFactory::PARSER_SIMPLE, $client->RUNTIME['PARSER']);

        // Test protocol
        $cls = $proxyClient->getJavaClass('java.lang.Class');
        $this->assertInstanceOf(JavaClass::class, $cls);

        $str = new Java('java.lang.String', 'Hello');
        $this->assertInstanceOf(JavaObject::class, $str);
        $len = $str->length();
        $this->assertSame(5, $len);

        // Clean up client instance
        $this->clearPjbProxyClientSingleton();
    }

    /**
     * Clears the protected static variables of PjbProxyClient to force reinitialization.
     */
    protected function clearPjbProxyClientSingleton()
    {
        PjbProxyClient::unregisterInstance();
        /*
        $refl = new \ReflectionClass(PjbProxyClient::class);
        $propertiesToClear = [
            'instance',
            'instanceOptionsKey',
            'client'
        ];

        foreach ($propertiesToClear as $propertyName) {
            $reflProperty = $refl->getProperty($propertyName);
            $reflProperty->setAccessible(true);
            $reflProperty->setValue(null, null);
            $reflProperty->setAccessible(false);
        }
        */
    }

    #[Test]
    public function overrideDefaultOptions(): void
    {
        $defaultOptions = (array) PjbProxyClient::getInstance($this->options)->getOptions();

        // For sake of simplicity just inverse the boolean default options
        $overriddenOptions = $defaultOptions;
        foreach ($overriddenOptions as $option => $value) {
            $overriddenOptions[$option] = is_bool($value) ? !$value : $value;
        }

        // Clear previous singleton to force re-creation of the object
        $this->clearPjbProxyClientSingleton();

        $options = (array) PjbProxyClient::getInstance($overriddenOptions)->getOptions();

        foreach ($options as $option => $value) {
            if (is_bool($value)) {
                $this->assertNotEquals($value, $defaultOptions[$option]);
            }
        }
    }
}
