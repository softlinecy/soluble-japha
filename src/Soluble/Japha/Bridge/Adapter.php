<?php

declare(strict_types=1);

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace Soluble\Japha\Bridge;

use Soluble\Japha\Bridge\Exception\UnsupportedDriverException;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Interfaces\JavaClass;
use Soluble\Japha\Bridge\Adapter\System;
use Soluble\Japha\Bridge\Driver\DriverInterface;
use Soluble\Japha\Bridge\Driver\Pjb62\Pjb62Driver;
use Soluble\Japha\Interfaces;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Adapter
{
    public const DEFAULT_DRIVER = 'pjb62';

    /**
     * @var string[]
     */
    protected static $registeredDrivers = [
        self::DEFAULT_DRIVER => Pjb62Driver::class
    ];

    /**
     * @var Driver\AbstractDriver
     */
    protected object $driver;

    /**
     * @var Adapter\System
     */
    protected $system;

    protected ?LoggerInterface $logger;

    /**
     * Constructor.
     * <code>
     * $ba = new Adapter([
     *     'driver' => 'Pjb62',
     *     'servlet_address' => 'http://127.0.0.1:8080/javabridge-bundle/java/servlet.phpjavabridge'
     *      //'use_persistent_connection' => false
     *      //'java_default_timezone' => null,
     *      //'java_prefer_values' => true,
     *      //'force_simple_xml_parser' => false
     *      //'java_log_level' => null // set it to 0,1,2,3,4,5,6 to see errors in tomcat log
     *    ]);
     * </code>
     *
     * @param  array<string, mixed>  $options
     * @param  LoggerInterface|null  $logger  any PSR-3 compatible logger
     */
    public function __construct(array $options, LoggerInterface $logger = null)
    {
        if (!$logger instanceof LoggerInterface) {
            $logger = new NullLogger();
        }
        
        $this->logger = $logger;

        $driver = isset($options['driver']) ? strtolower((string) $options['driver']) : self::DEFAULT_DRIVER;

        $driver_class = self::$registeredDrivers[$driver] ?? null;

        if ($driver_class === null) {
            throw new UnsupportedDriverException(__METHOD__.sprintf(" Driver '%s' is not supported", $driver));
        }

        $this->driver = new $driver_class($options, $logger);

        if (array_key_exists('java_default_timezone', $options)
            && $options['java_default_timezone'] !== null) {
            $this->setJavaDefaultTimezone($options['java_default_timezone']);
        }
    }

    /**
     * Create a new Java instance from a FQCN (constructor arguments are sent in a variadic way).
     *
     * <code>
     * $hash   = $ba->java('java.util.HashMap', ['key' => '保éà']);
     * echo $hash->get('key'); // prints "保éà"
     * </code>
     *
     * @param string     $class   Java class name (FQCN)
     * @param mixed|null ...$args arguments passed to the constructor of the java object
     *
     * @throws \Soluble\Japha\Bridge\Exception\JavaException
     * @throws \Soluble\Japha\Bridge\Exception\ClassNotFoundException
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     * @see \Soluble\Japha\Bridge\Adapter::javaClass for information about classes
     */
    public function java(string $class, ...$args): JavaObject
    {
        return $this->driver->instanciate($class, ...$args);
    }

    /**
     * Load a java class.
     *
     * <code>
     * $calendar = $ba->javaClass('java.util.Calendar')->getInstance();
     * $date = $calendar->getTime();
     *
     * $system = $ba->javaClass('java.lang.System');
     * echo  $system->getProperties()->get('java.vm_name);
     *
     * $tzClass = $ba->javaClass('java.util.TimeZone');
     * echo $tz->getDisplayName(false, $tzClass->SHORT);
     * </code>
     *
     * @see \Soluble\Japha\Bridge\Adapter::java() for object creation
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     * @throws \Soluble\Japha\Bridge\Exception\ClassNotFoundException
     *
     * @param string $class Java class name (FQCN)
     */
    public function javaClass(string $class): JavaClass
    {
        return $this->driver->getJavaClass($class);
    }

    /**
     * Checks whether object is an instance of a class or interface.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     * @throws \Soluble\Japha\Bridge\Exception\ClassNotFoundException
     * @throws \Soluble\Japha\Bridge\Exception\InvalidArgumentException
     *
     * @param string|Interfaces\JavaObject|Interfaces\JavaClass $className  java class name
     *
     */
    public function isInstanceOf(JavaObject $javaObject, $className): bool
    {
        return $this->driver->isInstanceOf($javaObject, $className);
    }

    /**
     * Return object java FQCN class name.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     */
    public function getClassName(JavaObject $javaObject): string
    {
        return $this->driver->getClassName($javaObject);
    }

    /**
     * Whether a java internal value is null.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     * @param Interfaces\JavaObject|null $javaObject
     */
    public function isNull(JavaObject $javaObject = null): bool
    {
        return $this->driver->isNull($javaObject);
    }

    /**
     * Check wether a java value is true (boolean).
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     */
    public function isTrue(JavaObject $javaObject): bool
    {
        return $this->driver->isTrue($javaObject);
    }

    /**
     * Return system properties.
     */
    public function getSystem(): System
    {
        if ($this->system === null) {
            $this->system = new System($this);
        }

        return $this->system;
    }

    /**
     * Fast retrieval of JavaObject values (one roundtrip),
     * use it on Java array structures (ArrayList, HashMap...)
     * to avoid the need of iterations on the PHP side.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     * @return mixed
     */
    public function values(JavaObject $javaObject)
    {
        return $this->driver->values($javaObject);
    }

    /**
     * Return underlying driver.
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Set the JVM/Java default timezone.
     *
     * Caution: this method should be used with care because it will change the global timezone
     * on the JVM or Bridge servlet. Better to configure it by other ways as every
     * scripts may change it.
     *
     *
     * @throws \Soluble\Japha\Util\Exception\UnsupportedTzException
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     */
    private function setJavaDefaultTimezone(string $timezone): void
    {
        $this->getSystem()->setTimeZoneId($timezone);
    }
}
