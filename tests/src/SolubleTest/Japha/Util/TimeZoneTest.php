<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Util;

use Soluble\Japha\Bridge\Adapter;
use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Util\Exception\UnsupportedTzException;
use Soluble\Japha\Util\Exception\InvalidArgumentException;
use Soluble\Japha\Bridge;
use Soluble\Japha\Util\TimeZone;
use Soluble\Japha\Interfaces;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class TimeZoneTest extends TestCase
{
    /**
     * @var string
     */
    protected $servlet_address;

    /**
     * @var Bridge\Adapter
     */
    protected $ba;

    /**
     * @var TimeZone
     */
    protected $timeZone;

    /**
     * @var Interfaces\JavaObject
     */
    protected $backupTz;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        \SolubleTestFactories::startJavaBridgeServer();
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
        $this->ba = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ]);

        $this->timeZone = new TimeZone($this->ba);
        $this->backupTz = $this->ba->javaClass('java.util.TimeZone')->getDefault();
        //var_dump($this->backupTz);
        //die();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        if ($this->ba !== null) {
            $this->ba->javaClass('java.util.TimeZone')->setDefault($this->backupTz);
            TimeZone::enableTzCache();
        }
    }

    #[Test]
    public function getAvailableIDs(): void
    {
        $availableTz = $this->timeZone->getAvailableIDs();
        $this->assertIsArray($availableTz);
        $this->assertContains('Europe/Paris', $availableTz);
    }

    #[Test]
    public function getDefault(): void
    {
        $default = $this->timeZone->getDefault();
        $this->assertInstanceOf(JavaObject::class, $default);
        $iof = $this->ba->isInstanceOf($default, 'java.util.TimeZone');
        $this->assertTrue($iof);
    }

    #[Test]
    public function getTimezone(): void
    {
        $ids = ['Europe/Paris', 'CET', 'GMT'];
        foreach ($ids as $id) {
            $tz = $this->timeZone->getTimeZone($id);
            $iof = $this->ba->isInstanceOf($tz, 'java.util.TimeZone');
            $this->assertTrue($iof);
            $this->assertEquals($id, (string) $tz->getID());

            $phpTz = new DateTimeZone($id);
            $tz = $this->timeZone->getTimeZone($phpTz);
            $iof = $this->ba->isInstanceOf($tz, 'java.util.TimeZone');
            $this->assertTrue($iof);
            $this->assertEquals($id, (string) $tz->getID());
        }
    }

    #[Test]
    public function getTimezoneThrowsUnsupportedTzException(): void
    {
        //TimeZone.getTimeZone("GMT-8").getID() returns "GMT-08:00".
        $this->expectException(UnsupportedTzException::class);
        $this->timeZone->getTimeZone('invalidTz');
    }

    #[Test]
    public function getTimezoneThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->timeZone->getTimeZone([0, 2, 3]);
    }

    #[Test]
    public function setDefault(): void
    {
        $originalTz = $this->timeZone->getDefault();
        $ids = ['Europe/Paris', 'CET', 'GMT'];
        foreach ($ids as $id) {
            $tz = $this->timeZone->getTimeZone($id);
            $this->timeZone->setDefault($tz);
            $default = $this->timeZone->getDefault()->getID();
            $this->assertEquals($id, (string) $default);
        }
        
        $this->timeZone->setDefault($originalTz);
    }

    #[Test]
    public function getDefaultEnableCache(): void
    {
        $originalTz = $this->timeZone->getDefault();

        $this->timeZone->setDefault('Europe/Paris');
        $parisTz = $this->timeZone->getDefault($enableTzCache = true)->getID();

        // native setting of a new timezone
        $newTz = $this->timeZone->getTimeZone('Europe/London');
        $this->ba->javaClass('java.util.TimeZone')->setDefault($newTz);
        $newDefault = $this->ba->javaClass('java.util.TimeZone')->getDefault()->getID();
        $this->assertSame('Europe/London', (string) $newDefault);

        // should produce same as previous (means wrong behaviour)
        $cachedTz = $this->timeZone->getDefault($enableTzCache = true)->getID();
        $this->assertSame((string) $parisTz, (string) $cachedTz);

        // with uncached you should have the new one
        $uncachedTz = $this->timeZone->getDefault($enableTzCache = false)->getID();
        $this->assertSame('Europe/London', (string) $uncachedTz);

        $this->timeZone->setDefault($originalTz);
    }

    #[Test]
    public function getDefaultStaticCache(): void
    {
        $originalTz = $this->timeZone->getDefault();

        TimeZone::disableTzCache();

        $this->timeZone->setDefault('Europe/Paris');
        $this->timeZone->getDefault($enableTzCache = true)->getID();

        // native setting of a new timezone
        $newTz = $this->timeZone->getTimeZone('Europe/London');
        $this->ba->javaClass('java.util.TimeZone')->setDefault($newTz);
        $newDefault = $this->ba->javaClass('java.util.TimeZone')->getDefault()->getID();
        $this->assertSame('Europe/London', (string) $newDefault);

        // should always produce the good behaviour
        $cachedTz = $this->timeZone->getDefault($enableTzCache = true)->getID();
        $this->assertSame('Europe/London', (string) $cachedTz);

        // with uncached you should have the new one
        $uncachedTz = $this->timeZone->getDefault($enableTzCache = false)->getID();
        $this->assertSame('Europe/London', (string) $uncachedTz);

        $this->timeZone->setDefault($originalTz);
    }
}
