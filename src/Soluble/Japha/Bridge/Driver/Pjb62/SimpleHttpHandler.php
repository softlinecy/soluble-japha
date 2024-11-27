<?php

declare(strict_types=1);

/**
 * soluble-japha / PHPJavaBridge driver client.
 *
 * Refactored version of phpjababridge's Java.inc file compatible
 * with php java bridge 6.2
 *
 *
 * @credits   http://php-java-bridge.sourceforge.net/pjb/
 *
 * @see      http://github.com/belgattitude/soluble-japha
 *
 * @author Jost Boekemeier
 * @author Vanvelthem Sébastien (refactoring and fixes from original implementation)
 * @license   MIT
 *
 * The MIT License (MIT)
 * Copyright (c) 2014-2017 Jost Boekemeier
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Soluble\Japha\Bridge\Driver\Pjb62;

use Soluble\Japha\Bridge\Exception\ConnectionException;
use Soluble\Japha\Bridge\Socket\StreamSocket;

class SimpleHttpHandler extends SocketHandler
{
    public $headers;

    /**
     * @var array
     */
    public $cookies = [];

    /**
     * @var string
     */
    public $context;

    /**
     * @var array
     */
    protected $cachedValues = [
        'getContext' => null
    ];

    /**
     * @throws Exception\IllegalStateException on channel creation error
     *
     * @param string   $ssl
     * @param string   $host
     * @param int      $port
     * @param string   $java_servlet
     * @param int      $java_recv_size
     * @param int      $java_send_size
     */
    public function __construct(Protocol $protocol, public $ssl, public $host, public $port, protected $java_servlet, protected $java_recv_size, protected $java_send_size)
    {
        $this->protocol = $protocol;
        $this->createChannel();
    }

    /**
     * @throws Exception\IllegalStateException     on channel creation error
     * @throws Exception\BrokenConnectionException if all goes wrong
     */
    protected function createChannel()
    {
        $channelName = Pjb62Driver::getJavaBridgeHeader('X_JAVABRIDGE_REDIRECT', $_SERVER);
        $context = Pjb62Driver::getJavaBridgeHeader('X_JAVABRIDGE_CONTEXT', $_SERVER);
        $len = strlen($context);
        $len0 = PjbProxyClient::getInstance()->getCompatibilityOption($this->protocol->client);
        $len1 = chr($len & 0xFF);
        $len >>= 8;
        $len2 = chr($len & 0xFF);
        $this->channel = $this->getChannel($channelName);
        $this->protocol->setSocketHandler(new SocketHandler($this->protocol, $this->channel));
        $this->protocol->write(sprintf('%s%s%s%s', $len0, $len1, $len2, $context));
        
        $this->context = sprintf("X_JAVABRIDGE_CONTEXT: %s\r\n", $context);
        $this->protocol->handler = $this->protocol->getSocketHandler();

        if ($this->protocol->client->sendBuffer !== null) {
            $ret = $this->protocol->handler->write($this->protocol->client->sendBuffer);
            if ($ret === null) {
                $this->protocol->handler->shutdownBrokenConnection('Broken local connection handle');
            }
            
            $this->protocol->client->sendBuffer = null;
            $this->protocol->handler->read(1);
        }
    }

    public function getContextFromCgiEnvironment(): string
    {
        return Pjb62Driver::getJavaBridgeHeader('X_JAVABRIDGE_CONTEXT', $_SERVER);
    }

    /**
     * @return string
     */
    public function getContext()
    {
        if (!array_key_exists('getContext', $this->cachedValues)) {
            $ctx = $this->getContextFromCgiEnvironment();
            if ($ctx) {
                $this->cachedValues['getContext'] = sprintf('X_JAVABRIDGE_CONTEXT: %s', $ctx);
            }
        }

        return $this->cachedValues['getContext'];
    }

    public function getWebAppInternal()
    {
        $context = $this->protocol->webContext;

        return $context ?? (($this->java_servlet == 'User' &&
                array_key_exists('PHP_SELF', $_SERVER) &&
                array_key_exists('HTTP_HOST', $_SERVER)) ? $_SERVER['PHP_SELF'].'javabridge' : null);
    }

    public function getWebApp(): string
    {
        $context = $this->getWebAppInternal();
        if (null === $context) {
            return $this->java_servlet;
        }

        return $context;
    }

    /**
     * @throws Exception\BrokenConnectionException
     */
    public function write(string $data): ?int
    {
        return $this->protocol->getSocketHandler()->write($data);
    }

    public function doSetCookie($key, $val, $path): void
    {
        $path = trim((string) $path);
        $webapp = $this->getWebAppInternal();
        if (!$webapp) {
            $path = '/';
        }
        
        setcookie($key, (string) $val, ['expires' => 0, 'path' => $path]);
    }

    /**
     * @throws Exception\BrokenConnectionException
     */
    public function read(int $size): string
    {
        return $this->protocol->getSocketHandler()->read($size);
    }

    /**
     * @param string $channelName generally the tcp port on which to connect
     *
     *
     * @throws Exception\ConnectException
     */
    public function getChannel(string $channelName): SocketChannelP
    {
        $persistent = $this->protocol->client->getParam(Client::PARAM_USE_PERSISTENT_CONNECTION);
        try {
            $streamSocket = new StreamSocket(
                $this->ssl === 'ssl://' ? StreamSocket::TRANSPORT_SSL : StreamSocket::TRANSPORT_TCP,
                $this->host.':'.$channelName,
                null,
                [],
                $persistent
            );
            $socket = $streamSocket->getSocket();
        } catch (\Throwable $throwable) {
            $logger = $this->protocol->getClient()->getLogger();
            $logger->critical(sprintf(
                '[soluble-japha] %s (%s)',
                $throwable->getMessage(),
                __METHOD__
            ));
            throw new ConnectionException($throwable->getMessage(), $throwable->getCode());
        }
        
        stream_set_timeout($socket, -1);

        return new SocketChannelP($socket, $this->host, $this->java_recv_size, $this->java_send_size);
    }

    public function keepAlive(): void
    {
        parent::keepAlive();
    }

    public function redirect(): void
    {
    }
}
