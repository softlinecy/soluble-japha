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
use Soluble\Japha\Interfaces;

abstract class AbstractDriver implements DriverInterface
{
    const CAST_TYPE_STRING = 'string';
    
    const CAST_TYPE_BOOLEAN = 'boolean';
    
    const CAST_TYPE_INTEGER = 'integer';
    
    const CAST_TYPE_FLOAT = 'float';
    
    const CAST_TYPE_ARRAY = 'array';
    
    const CAST_TYPE_NULL = 'null';
    
    const CAST_TYPE_OBJECT = 'object';

    /**
     * {@inheritdoc}
     */
    abstract public function instanciate(string $class_name, ...$args): JavaObject;

    /**
     * Fast retrieval of JavaObject values (one roundtrip),
     * use it on Java array structures (ArrayList...)
     * to avoid the need of iterations on the PHP side.
     *
     *
     * @return mixed
     */
    abstract public function values(JavaObject $javaObject);

    /**
     * Inspect object.
     *
     *
     */
    abstract public function inspect(JavaObject $javaObject): string;

    /**
     * {@inheritdoc}
     */
    abstract public function isInstanceOf(JavaObject $javaObject, $className): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function getClassName(JavaObject $javaObject): string;

    /**
     * {@inheritdoc}
     */
    abstract public function getJavaClass(string $class_name): JavaClass;

    /**
     * {@inheritdoc}
     */
    abstract public function invoke(string $method, JavaType $javaObject = null, array $args = []);

    /**
     * {@inheritdoc}
     */
    abstract public function getContext(): JavaObject;

    /**
     * Return java servlet session.
     *
     * <code>
     * $session = $adapter->getDriver()->getJavaSession();
     * $counter = $session->get('counter');
     * if ($adapter->isNull($counter)) {
     *    $session->put('counter', 1);
     * } else {
     *    $session->put('counter', $counter + 1);
     * }
     * </code>
     *
     *
     */
    abstract public function getJavaSession(array $args = []): JavaObject;

    /**
     * Cast a java object into a php type.
     *(see self::CAST_TYPE_*).
     *
     * @throws \Soluble\Japha\Bridge\Exception\RuntimeException
     *
     * @param Interfaces\JavaObject|int|float|array|bool $javaObject
     *
     * @return mixed
     */
    abstract public function cast($javaObject, string $cast_type);

    /**
     * {@inheritdoc}
     */
    public function isNull(JavaObject $javaObject = null): bool
    {
        if (!$javaObject instanceof JavaObject) {
            return true;
        }

        return $this->values($javaObject) === null;
    }

    /**
     * {@inheritdoc}
     */
    public function isTrue(JavaObject $javaObject): bool
    {
        $values = $this->values($javaObject);
        if (is_int($values) || is_bool($values)) {
            return $values == true;
        }

        return false;
    }
}
