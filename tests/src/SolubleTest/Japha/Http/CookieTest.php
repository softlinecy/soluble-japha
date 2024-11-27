<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Soluble\Japha\Bridge\Http\Cookie;
use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    #[Test]
    public function nullHeaderLine(): void
    {
        $this->assertNull(Cookie::getCookiesHeaderLine([]));
    }

    #[DataProvider('cookiesProvider')]
    #[Test]
    public function getCookiesHeaderLine(array $cookies, string $expectedString): void
    {
        $expectedString = 'Cookie: ' . $expectedString;
        $cookieString = Cookie::getCookiesHeaderLine($cookies);

        $urlDecodedString = urldecode((string) $cookieString);

        $this->assertSame($expectedString, $urlDecodedString, 'test that cookie was correctly serialized');
    }

    public static function cookiesProvider(): \Iterator
    {
        // scenario: single scalar
        yield [
            // Original cookies
            [
                'cookieName' => 'cookieValue'
            ],
            // Serialized string
            'cookieName=cookieValue'
        ];
        // scenario: two scalars
        yield [
            // Original cookies
            [
                'stringCookie' => 'cookieValue',
                'integerCookie' => 123,
            ],
            // Serialized string
            'stringCookie=cookieValue;integerCookie=123'
        ];
        // scenario: booleans and null
        yield [
            // Original cookies
            [
                'booleanCookieFalse' => false,
                'booleanCookieTrue' => true,
                'nullCookie' => null
            ],
            // Serialized string
            'booleanCookieFalse=0;booleanCookieTrue=1;nullCookie='
        ];
        // scenario: complex array
        yield [
            // Original cookies
            [
                'complexArrayCookie' => [
                    'firstNumericItem' => 1,
                    'secondBooleanItem' => false,
                    'thirdNullItem' => null,
                    'fourthArrayItem' => [
                        1,      // index 0
                        'two',  // index 1
                        true,   // index 2
                        ['ABC'], // index 3
                        'key' => 'value' // index 'key',
                    ]
                ]
            ],
            // Serialized string
            'complexArrayCookie[firstNumericItem]=1;'
            .'complexArrayCookie[secondBooleanItem]=0;'
            .'complexArrayCookie[thirdNullItem]=;'
            .'complexArrayCookie[fourthArrayItem][0]=1;'
            .'complexArrayCookie[fourthArrayItem][1]=two;'
            .'complexArrayCookie[fourthArrayItem][2]=1;'
            .'complexArrayCookie[fourthArrayItem][3][0]=ABC;'
            .'complexArrayCookie[fourthArrayItem][key]=value'
        ];
        // scenario: unsupported types
        yield [
            // Original cookies
            [
                'dateTimeObject' => new \DateTime(),
                'function' => function (): void {
                },
            ],
            // Serialized string
            'dateTimeObject='.Cookie::UNSUPPORTED_TYPE_VALUE.';function='.Cookie::UNSUPPORTED_TYPE_VALUE
        ];
    }
}
