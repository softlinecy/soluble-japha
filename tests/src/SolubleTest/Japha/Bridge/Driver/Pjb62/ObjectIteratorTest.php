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
use Soluble\Japha\Bridge\Driver\Pjb62\ObjectIterator;

class ObjectIteratorTest extends TestCase
{
    #[Test]
    public function iteratorMethodsDeclareCompatibleReturnTypes(): void
    {
        $this->assertSame('mixed', (string) (new \ReflectionMethod(ObjectIterator::class, 'current'))->getReturnType());
        $this->assertSame('void', (string) (new \ReflectionMethod(ObjectIterator::class, 'next'))->getReturnType());
        $this->assertSame('mixed', (string) (new \ReflectionMethod(ObjectIterator::class, 'key'))->getReturnType());
    }
}
