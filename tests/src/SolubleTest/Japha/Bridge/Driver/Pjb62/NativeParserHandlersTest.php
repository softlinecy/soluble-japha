<?php
declare(strict_types=1);

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
use Soluble\Japha\Bridge\Driver\Pjb62\NativeParser;

/**
 * Runs without a php-java-bridge server: the client is mocked.
 */
class NativeParserHandlersTest extends TestCase
{
    /**
     * PHP 8.4 resolves a handler string that names a global function to that function,
     * so 'end' called PHP's end() instead of NativeParser::end().
     */
    #[Test]
    public function elementHandlersAreDispatchedToTheParser(): void
    {
        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $clientMock->java_recv_size = 8192;
        $clientMock->method('read')->willReturn('<A n="1"></A>');

        $events = [];
        $clientMock->method('begin')->willReturnCallback(function (string $name, array $param) use (&$events): void {
            $events[] = ['begin', $name, $param];
        });
        $clientMock->method('end')->willReturnCallback(function (string $name) use (&$events): void {
            $events[] = ['end', $name];
        });

        $parser = new NativeParser($clientMock);
        $parser->parse();

        // <F> is the root element the constructor opens; it is never closed.
        $this->assertSame([
            ['begin', 'F', []],
            ['begin', 'A', ['n' => '1']],
            ['end', 'A'],
        ], $events);
    }
}
