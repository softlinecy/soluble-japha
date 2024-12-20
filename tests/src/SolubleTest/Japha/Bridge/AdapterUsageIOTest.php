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
use Soluble\Japha\Bridge\Adapter;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-04 at 16:47:42.
 */
class AdapterUsageIOTest extends TestCase
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
    public function readingFiles(): void
    {
        // Server must be on localhost in order to pass.
        $dir = __DIR__;
        $f = $this->adapter->java('java.io.File', $dir);
        $paths = $f->listFiles();
        foreach ($paths as $path) {
            $this->assertFileExists((string) $path);
        }
    }

    #[Test]
    public function socketFactory(): void
    {
        $ba = $this->adapter;

        $serverPort = 443;
        $host = 'www.google.com';

        $socketFactory = $ba->javaClass('javax.net.ssl.SSLSocketFactory')->getDefault();
        $socket = $socketFactory->createSocket($host, $serverPort);

        $socket->startHandshake();
        
        $bufferedWriter = $ba->java(
            'java.io.BufferedWriter',
            $ba->java('java.io.OutputStreamWriter', $socket->getOutputStream())
        );

        $bufferedReader = $ba->java(
            'java.io.BufferedReader',
            $ba->java('java.io.InputStreamReader', $socket->getInputStream())
        );

        $bufferedWriter->write('GET / HTTP/1.0');
        $bufferedWriter->newLine();
        $bufferedWriter->newLine(); // end of HTTP request
        $bufferedWriter->flush();

        $lines = [];
        do {
            $line = $bufferedReader->readLine();
            $lines[] = (string) $line;
        } while (!$ba->isNull($line));

        $content = implode("\n", $lines);
        // echo $content;

        $bufferedWriter->close();
        $bufferedReader->close();
        $socket->close();

        $this->assertGreaterThan(0, count($lines));
        $this->assertStringContainsString('HTTP/1.0', $content);
    }
}
