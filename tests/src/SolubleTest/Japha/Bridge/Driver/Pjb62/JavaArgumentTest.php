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
use Soluble\Japha\Bridge\Driver\Pjb62\Exception\IllegalArgumentException;
use Soluble\Japha\Bridge\Driver\Pjb62\Java;
use Soluble\Japha\Bridge\Driver\Pjb62\PjbProxyClient;

class JavaArgumentTest extends TestCase
{
    #[Test]
    public function constructorThrowsIllegalArgumentExceptionForClosedResource(): void
    {
        $this->withFakeProxyClient(function (): void {
            $resource = $this->closedResource();

            $this->expectException(IllegalArgumentException::class);
            $this->expectExceptionMessage('resource (closed)');

            new Java('example.Type', $resource);
        });
    }

    #[Test]
    public function methodCallThrowsIllegalArgumentExceptionForClosedResource(): void
    {
        $java = (new \ReflectionClass(Java::class))->newInstanceWithoutConstructor();
        $java->__client = $this->createMock(Client::class);
        $java->__java = 1;
        $java->__signature = 'Lexample/Type;';
        $resource = $this->closedResource();

        $this->expectException(IllegalArgumentException::class);
        $this->expectExceptionMessage('resource (closed)');

        $java->unsupportedMethod($resource);
    }

    private function withFakeProxyClient(callable $callback): void
    {
        $reflection = new \ReflectionClass(PjbProxyClient::class);
        $staticProperties = ['instance', 'client', 'instanceOptionsKey', 'unregistering'];
        $previousValues = [];

        foreach ($staticProperties as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $previousValues[$propertyName] = $property->getValue();
        }

        try {
            $client = $this->createMock(Client::class);
            $client->method('getParam')
                ->with(Client::PARAM_JAVA_INTERNAL_ENCODING)
                ->willReturn('UTF-8');

            $reflection->getProperty('instance')->setValue(null, $reflection->newInstanceWithoutConstructor());
            $reflection->getProperty('client')->setValue(null, $client);
            $reflection->getProperty('instanceOptionsKey')->setValue(null, null);
            $reflection->getProperty('unregistering')->setValue(null, false);

            $callback();
        } finally {
            foreach ($previousValues as $propertyName => $previousValue) {
                $reflection->getProperty($propertyName)->setValue(null, $previousValue);
            }
        }
    }

    private function closedResource()
    {
        $resource = fopen('php://memory', 'r+');
        $this->assertIsResource($resource);
        fclose($resource);

        return $resource;
    }
}
