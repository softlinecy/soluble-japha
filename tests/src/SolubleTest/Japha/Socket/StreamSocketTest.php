<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Socket;

use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Bridge\Exception\InvalidArgumentException;
use Soluble\Japha\Bridge\Exception\ConnectionException;
use Soluble\Japha\Bridge\Socket\StreamSocket;
use PHPUnit\Framework\TestCase;

class StreamSocketTest extends TestCase
{
    /**
     * @var string
     */
    protected $servlet_address;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        \SolubleTestFactories::startJavaBridgeServer();

        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
    }

    #[Test]
    public function invalidTransportThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StreamSocket('invalid', 'localhost');
    }

    #[Test]
    public function throwsConnectionException(): void
    {
        $this->expectException(ConnectionException::class);
        //$this->expectExceptionMessage('cooo');
        new StreamSocket(
            StreamSocket::TRANSPORT_TCP,
            '128.23.60.12:73567',
            0.5
        );
    }

    protected function getWorkingStreamSocket(): StreamSocket
    {
        [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port
        ] = parse_url($this->servlet_address);

        return new StreamSocket(
            $scheme === 'https' ? StreamSocket::TRANSPORT_SSL : StreamSocket::TRANSPORT_TCP,
            sprintf('%s:%d', $host, $port),
            2.0
        );
    }

    #[Test]
    public function getTransport(): void
    {
        $streamSocket = $this->getWorkingStreamSocket();
        [
            'scheme' => $scheme,
        ] = parse_url($this->servlet_address);
        $transport = $scheme === 'https' ? StreamSocket::TRANSPORT_SSL : StreamSocket::TRANSPORT_TCP;
        $this->assertSame($transport, $streamSocket->getTransport());
    }

    #[Test]
    public function getSocket(): void
    {
        $streamSocket = $this->getWorkingStreamSocket();
        $this->assertIsResource($streamSocket->getSocket());
    }

    #[Test]
    public function getStreamAddress(): void
    {
        $streamSocket = $this->getWorkingStreamSocket();
        [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port
        ] = parse_url($this->servlet_address);
        $transport = $scheme === 'https' ? StreamSocket::TRANSPORT_SSL : StreamSocket::TRANSPORT_TCP;

        $this->assertSame(sprintf('%s://%s:%d', $transport, $host, $port), $streamSocket->getStreamAddress());
    }

    #[Test]
    public function getConnectTimeoutWithDefaults(): void
    {
        $streamSocketMock = $this->getMockBuilder(StreamSocket::class)
             ->enableOriginalConstructor();

        $streamSocket = $streamSocketMock->setConstructorArgs([
            StreamSocket::TRANSPORT_TCP,
            '127.0.0.1:8080',
            1.5
        ])->getMock();
        $this->assertEqualsWithDelta(1.5, $streamSocket->getConnectTimeout(), PHP_FLOAT_EPSILON);

        $streamSocket = $streamSocketMock->setConstructorArgs([
            StreamSocket::TRANSPORT_TCP,
            '127.0.0.1:8080'
        ])->getMock();
        $this->assertSame(StreamSocket::DEFAULT_CONNECT_TIMEOUTS['HOST_127.0.0.1'], $streamSocket->getConnectTimeout());

        $streamSocket = $streamSocketMock->setConstructorArgs([
            StreamSocket::TRANSPORT_TCP,
            'localhost:8080'
        ])->getMock();
        $this->assertSame(StreamSocket::DEFAULT_CONNECT_TIMEOUTS['HOST_localhost'], $streamSocket->getConnectTimeout());

        $streamSocket = $streamSocketMock->setConstructorArgs([
            StreamSocket::TRANSPORT_TCP,
            '257.257.257.257:8080'
        ])->getMock();
        $this->assertSame(StreamSocket::DEFAULT_CONNECT_TIMEOUTS['DEFAULT'], $streamSocket->getConnectTimeout());
    }

    #[Test]
    public function isPersistent(): void
    {
        $streamSocketMock = $this->getMockBuilder(StreamSocket::class)
            ->enableOriginalConstructor();

        // DEFAULT
        $streamSocket = $streamSocketMock->setConstructorArgs([
            StreamSocket::TRANSPORT_TCP,
            '127.0.0.1:8080',
            null
        ])->getMock();
        $this->assertFalse($streamSocket->isPersistent());

        // TRUE
        $streamSocket = $streamSocketMock->setConstructorArgs([
            StreamSocket::TRANSPORT_TCP,
            '127.0.0.1:8080',
            null,
            [],
            $persistent = true,
        ])->getMock();
        $this->assertTrue($streamSocket->isPersistent());
    }
}
