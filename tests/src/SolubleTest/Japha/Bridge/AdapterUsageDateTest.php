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
use Soluble\Japha\Bridge\Driver\AbstractDriver;
use Soluble\Japha\Bridge\Adapter;
use PHPUnit\Framework\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-04 at 16:47:42.
 */
class AdapterUsageDateTest extends TestCase
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
    public function getDriver(): void
    {
        $driver = $this->adapter->getDriver();
        $this->assertInstanceOf(AbstractDriver::class, $driver);
    }

    #[Test]
    public function date(): void
    {
        $ba = $this->adapter;

        // Step 1: Check with system java timezone

        $pattern = 'yyyy-MM-dd HH:mm';
        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone('UTC');
        $formatter->setTimeZone($tz);

        $first = $formatter->format($ba->java('java.util.Date', 0));
        $this->assertSame('1970-01-01 00:00', $first);

        $systemJavaTz = (string) $formatter->getTimeZone()->getId();

        $dateTime = new \DateTime(null, new \DateTimeZone($systemJavaTz));

        $now = $formatter->format($ba->java('java.util.Date'));
        $this->assertSame($dateTime->format('Y-m-d H:i'), $now);

        // Step 2: Check with system php timezone

        $pattern = 'yyyy-MM-dd HH:mm';
        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);
        $systemPhpTz = date_default_timezone_get();
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone($systemPhpTz);
        $formatter->setTimeZone($tz);

        $dateTime = new \DateTime(null);

        $now = $formatter->format($ba->java('java.util.Date'));
        $this->assertSame($dateTime->format('Y-m-d H:i'), $now);

        // Step 3: Different Timezones (europe/london and europe/paris -> 1 hour difference)

        $pattern = 'yyyy-MM-dd HH:mm:ss';

        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);

        $phpTz = new \DateTimeZone('Europe/Paris');

        $reference_date = '2012-11-07 12:52:23';
        $phpDate = \DateTime::createFromFormat('Y-m-d H:i:s', $reference_date, $phpTz);

        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone('Europe/Paris'));
        $date = $formatter->parse($reference_date);
        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone('Europe/London'));
        $javaDate = (string) $formatter->format($date);
        $this->assertNotSame($phpDate->format('Y-m-d H:i:s'), $javaDate);
        $this->assertSame($reference_date, $phpDate->format('Y-m-d H:i:s'));

        $phpDate->sub(new \DateInterval('PT1H'));
        $this->assertSame($phpDate->format('Y-m-d H:i:s'), $javaDate);
    }

    #[Test]
    public function sqlDate(): void
    {
        $ba = $this->adapter;

        $tz = 'Europe/Paris';
        $phpTz = new \DateTimeZone($tz);

        $pattern = 'yyyy-MM-dd HH:mm:ss';
        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);
        $reference_date = '2012-11-07 12:52:23';

        \DateTime::createFromFormat('Y-m-d H:i:s', $reference_date, $phpTz);

        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone('Europe/Paris'));
        $javaDate = $formatter->parse($reference_date);

        $sqlDate = $ba->java('java.sql.Date', $javaDate->getTime());

        $newDate = $sqlDate->valueOf('2012-11-07');
        $this->assertSame((string) $newDate->toString(), (string) $sqlDate->toString());
    }

    #[Test]
    public function dateStrToTimeMilliseconds(): void
    {
        // Simple date milliseconds
        $ba = $this->adapter;
        $expectations = [
            '2012-12-31 23:59:59',
            '2015-01-01 00:00:00'
        ];
        $pattern = 'yyyy-MM-dd HH:mm:ss';
        $simpleDateFormat = $ba->java('java.text.SimpleDateFormat', $pattern);
        $systemPhpTz = date_default_timezone_get();
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone($systemPhpTz);
        $simpleDateFormat->setTimeZone($tz);

        foreach ($expectations as $date) {
            $phpMilli = (strtotime($date) * 1000);
            $jDate = $ba->java('java.util.Date', $phpMilli);
            $formattedDate = (string) $simpleDateFormat->format($jDate);
            $this->assertEquals($date, $formattedDate);
        }

        // When strtotime fails

        $faultyDate = '2012-12-34 23:59:59';
        $phpMilli = (strtotime($faultyDate) * 1000);
        $this->assertSame(0, $phpMilli);
        $jDate = $ba->java('java.util.Date', $phpMilli);
        // To limit issues with different timezones
        // just check the date part
        $dateFormatter = $ba->java('java.text.SimpleDateFormat', 'yyyy-MM-dd');
        $this->assertSame('1970-01-01', (string) $dateFormatter->format($jDate));
    }

    #[Test]
    public function dateWithDateTime(): void
    {
        $ba = $this->adapter;
        $expectations = [
            '2012-12-31',
            '2015-01-01'
        ];

        $jDateFormatter = $ba->java('java.text.SimpleDateFormat', 'yyyy-MM-dd');
        $systemPhpTz = date_default_timezone_get();
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone($systemPhpTz);
        $jDateFormatter->setTimeZone($tz);

        foreach ($expectations as $value) {
            $phpDate = \DateTime::createFromFormat('Y-m-d', $value);
            $milli = $phpDate->format('U') * 1000;

            $javaDate = $ba->java('java.util.Date', $milli);

            $parsedJavaDate = $jDateFormatter->parse($value);

            $this->assertEquals($value, (string) $jDateFormatter->format($javaDate));
            $this->assertEquals($value, (string) $jDateFormatter->format($parsedJavaDate));
        }
    }

    #[Test]
    public function dateAdvanced(): void
    {
        $ba = $this->adapter;

        // Step 1: Check with system java timezone

        $pattern = 'yyyy-MM-dd HH:mm';
        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone('UTC');
        $formatter->setTimeZone($tz);

        $first = $formatter->format($ba->java('java.util.Date', 0));
        $this->assertSame('1970-01-01 00:00', $first);

        $systemJavaTz = (string) $formatter->getTimeZone()->getId();

        $dateTime = new \DateTime(null, new \DateTimeZone($systemJavaTz));

        $now = $formatter->format($ba->java('java.util.Date'));
        $this->assertSame($dateTime->format('Y-m-d H:i'), $now);

        // Step 2: Check with system php timezone

        $pattern = 'yyyy-MM-dd HH:mm';
        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);
        $systemPhpTz = date_default_timezone_get();
        $tz = $ba->javaClass('java.util.TimeZone')->getTimezone($systemPhpTz);
        $formatter->setTimeZone($tz);

        $dateTime = new \DateTime(null);

        $now = $formatter->format($ba->java('java.util.Date'));
        $this->assertSame($dateTime->format('Y-m-d H:i'), $now);

        // Step 3: Different Timezones (europe/london and europe/paris -> 1 hour difference)

        $pattern = 'yyyy-MM-dd HH:mm:ss';

        $formatter = $ba->java('java.text.SimpleDateFormat', $pattern);

        $phpTz = new \DateTimeZone('Europe/Paris');

        $reference_date = '2012-11-07 12:52:23';
        $phpDate = \DateTime::createFromFormat('Y-m-d H:i:s', $reference_date, $phpTz);

        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone('Europe/Paris'));
        $date = $formatter->parse($reference_date);
        $formatter->setTimeZone($ba->javaClass('java.util.TimeZone')->getTimezone('Europe/London'));
        $javaDate = (string) $formatter->format($date);
        $this->assertNotSame($phpDate->format('Y-m-d H:i:s'), $javaDate);
        $this->assertSame($reference_date, $phpDate->format('Y-m-d H:i:s'));

        $phpDate->sub(new \DateInterval('PT1H'));
        $this->assertSame($phpDate->format('Y-m-d H:i:s'), $javaDate);
    }
}
