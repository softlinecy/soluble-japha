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
use PHPUnit\Framework\TestCase;
use Soluble\Japha\Bridge\Driver\Pjb62\Client;
use Soluble\Japha\Bridge\Driver\Pjb62\Protocol;
use Soluble\Japha\Bridge\Driver\Pjb62\SimpleHttpTunnelHandler;

class SimpleHttpTunnelHandlerTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    #[Test]
    public function includesContextHeaderOnInitialCacheMiss(): void
    {
        $_SERVER['X_JAVABRIDGE_CONTEXT'] = '/my-web-app';
        unset($_SERVER['HTTP_X_JAVABRIDGE_CONTEXT']);

        $headers = $this->buildHeadersPayload();

        $this->assertStringContainsString('X_JAVABRIDGE_CONTEXT: /my-web-app', $headers);
    }

    #[Test]
    public function omitsContextHeaderWhenContextIsAbsent(): void
    {
        unset($_SERVER['X_JAVABRIDGE_CONTEXT'], $_SERVER['HTTP_X_JAVABRIDGE_CONTEXT']);

        $headers = $this->buildHeadersPayload();

        $this->assertStringNotContainsString('X_JAVABRIDGE_CONTEXT:', $headers);
    }

    private function buildHeadersPayload(): string
    {
        $client = $this->createMock(Client::class);
        $client->method('getParam')->willReturn(null);

        $protocol = $this->createMock(Protocol::class);
        $protocol->client = $client;
        $protocol->webContext = null;
        $protocol->method('getClient')->willReturn($client);

        $handler = (new \ReflectionClass(SimpleHttpTunnelHandler::class))->newInstanceWithoutConstructor();
        $handler->protocol = $protocol;
        (new \ReflectionProperty(SimpleHttpTunnelHandler::class, 'java_servlet'))->setValue($handler, 'servlet.phpjavabridge');
        $handler->host = 'bridge.example.test';
        $handler->port = 8080;

        return (new \ReflectionMethod(SimpleHttpTunnelHandler::class, 'getHttpHeadersPayload'))->invoke($handler);
    }
}
