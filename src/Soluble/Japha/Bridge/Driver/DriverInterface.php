<?php

declare(strict_types=1);

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace Soluble\Japha\Bridge\Driver;

use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Interfaces\JavaClass;
use Soluble\Japha\Interfaces\JavaType;
use Psr\Log\LoggerInterface;
use Soluble\Japha\Interfaces;

interface DriverInterface extends ConnectionInterface
{
    /**
     * DriverInterface constructor.
     */
    public function __construct(array $options, LoggerInterface $logger = null);

    /**
     * Instanciate a new java object.
     *
     * @throws \Soluble\Japha\Bridge\Exception\ClassNotFoundException
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     * @param string     $class_name Java FQCN i.e: 'java.lang.String'
     * @param mixed|null ...$args    arguments as variadic notation
     */
    public function instanciate(string $class_name, ...$args): JavaObject;

    /**
     * Return a new java class.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     * @throws \Soluble\Japha\Bridge\Exception\ClassNotFoundException
     *
     * @param string $class_name Java class FQCN i.e: 'java.lang.String'
     */
    public function getJavaClass(string $class_name): JavaClass;

    /**
     * Whether object is an instance of specific java class or interface.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     * @throws \Soluble\Japha\Bridge\Exception\ClassNotFoundException
     *
     * @param string|Interfaces\JavaClass|Interfaces\JavaObject $className  java class or interface name
     *
     */
    public function isInstanceOf(JavaObject $javaObject, $className): bool;

    /**
     * Return object java class name.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     */
    public function getClassName(JavaObject $javaObject): string;

    /**
     * Inspect object.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     */
    public function inspect(JavaObject $javaObject): string;

    /**
     * Invoke a method on a JavaObject (or a static method on a JavaClass).
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     * @param Interfaces\JavaType $javaObject javaObject can be Interfaces\JavaClass or Interfaces\JavaObject, if null use servlet methods registered on th JavaBridge side
     * @param string              $method     Method name on the JavaObject or JavaClass
     * @param array               $args       arguments
     *
     * @return mixed
     */
    public function invoke(string $method, JavaType $javaObject = null, array $args = []);

    /**
     * Check whether a java value is null.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     */
    public function isNull(JavaObject $javaObject = null): bool;

    /**
     * Check whether a java value is true (boolean and int values are considered).
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     */
    public function isTrue(JavaObject $javaObject): bool;

    /**
     * Returns the jsr223 script context handle.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     */
    public function getContext(): JavaObject;

    /**
     * One round trip retrieval of Java object value representation.
     *
     * @throws \Soluble\Japha\Bridge\Exception\BrokenConnectionException
     *
     *
     * @return mixed
     */
    public function values(JavaObject $javaObject);
}
