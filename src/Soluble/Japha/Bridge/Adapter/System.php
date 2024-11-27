<?php

declare(strict_types=1);

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace Soluble\Japha\Bridge\Adapter;

use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Interfaces\JavaObject;
use DateTimeZone;
use Soluble\Japha\Bridge;
use Soluble\Japha\Util\TimeZone;
use Soluble\Japha\Util\Exception\UnsupportedTzException;

class System
{

    protected TimeZone $timeZone;

    public function __construct(protected Adapter $ba)
    {
        $this->timeZone = new TimeZone($ba);
    }

    /**
     * Get php DateTime helper object.
     */
    public function getTimeZone(): TimeZone
    {
        return $this->timeZone;
    }

    /**
     * Return system default timezone id.
     */
    public function getTimeZoneId(): string
    {
        return (string) $this->timeZone->getDefault()->getID();
    }

    /**
     * Set system default timezone.
     *
     *@throws Bridge\Exception\JavaException
     * @throws UnsupportedTzException
     */
    public function setTimeZoneId(string|JavaObject|DateTimeZone $timezone): void
    {
        $this->timeZone->setDefault($timezone);
    }
}
