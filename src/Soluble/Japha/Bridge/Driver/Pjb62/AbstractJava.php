<?php

declare(strict_types=1);
/**
 * soluble-japha / PHPJavaBridge driver client.
 *
 * Refactored version of phpjababridge's Java.inc file compatible
 * with php java bridge 6.2
 *
 *
 * @credits   http://php-java-bridge.sourceforge.net/pjb/
 *
 * @see      http://github.com/belgattitude/soluble-japha
 *
 * @author Jost Boekemeier
 * @author Vanvelthem Sébastien (refactoring and fixes from original implementation)
 * @license   MIT
 *
 * The MIT License (MIT)
 * Copyright (c) 2014-2017 Jost Boekemeier
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @method string getName()
 * @method string forName()
 */

namespace Soluble\Japha\Bridge\Driver\Pjb62;

use Soluble\Japha\Interfaces\JavaObject;
use Exception;
use Soluble\Japha\Interfaces;
use Traversable;

abstract class AbstractJava implements \IteratorAggregate, \ArrayAccess, JavaType, JavaObject
{
    /**
     * @var Client|string|null
     */
    public $__client;

    /**
     * @var JavaType&JavaProxy
     */
    public $__delegate;

    protected $__serialID;

    public $__factory;

    /**
     * @var int
     */
    public $__java;

    /**
     * @var string|null
     */
    public $__signature;

    public $__cancelProxyCreationTag;

    protected function __createDelegate(): void
    {
        $proxy = $this->__factory->create($this->__java, $this->__signature);
        $this->__delegate = $proxy;
        $this->__java = $proxy->__java;
        $this->__signature = $proxy->__signature;
    }


    public function __cast(string $type) : mixed
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        return $this->__delegate->__cast($type);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        $this->__delegate->__sleep();

        return ['__delegate'];
    }

    public function __wakeup()
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        $this->__delegate->__wakeup();
        $this->__java = $this->__delegate->get__java();
        $this->__client = $this->__delegate->get__signature();
    }

    /**
     * Delegate the magic method __get() to the java object
     * to access the Java object properties (and not the PHP
     * remote proxied object).
     *
     * @throws \Exception Depending on ThrowExceptionProxy
     *
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        return $this->__delegate->__get($key);
    }

    /**
     * Delegate the magic method __set() to the java object
     * to access the Java object properties (and not the PHP
     * remote proxied object).
     *
     * @throws \Exception Depending on ThrowExceptionProxy
     *
     * @param mixed  $val
     */
    public function __set(string $key, $val): void
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        $this->__delegate->__set($key, $val);
    }

    /**
     * Delegate the magic method __cal() to the java object
     * to access the Java object method (and not the PHP
     * remote proxied object).
     *
     *
     * @return mixed
     *@throws \Exception Depending on ThrowExceptionProxy
     */
    public function __call(string $name, array $arguments)
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        return $this->__delegate->__call($name, $arguments);
    }

    /**
     * @param  string|int  $offset
     * @param  mixed|null  ...$args
     *
     * @throws Exception
     */
    public function offsetExists($offset, ...$args): bool
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }
        if (empty($args)) {
            return $this->__delegate->offsetExists($offset);
        }

        // In case we supplied more arguments than what ArrayAccess
        // suggest, let's try for a java method called offsetExists
        // with all the provided parameters
        array_unshift($args, $offset); // Will add idx at the beginning of args params

        return $this->__call('offsetExists', $args);
    }

    /**
     * @param  mixed|null  ...$args  arguments
     *
     * @throws Exception
     */
    public function getIterator(...$args) : Traversable
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        if (empty($args)) {
            return $this->__delegate->getIterator();
        }

        return $this->__call('getIterator', $args);
    }

    /**
     * @param  string|int  $offset
     * @param  mixed|null  ...$args  additional arguments
     *
     * @throws Exception
     */
    public function offsetGet($offset, ...$args): mixed
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }
        if (empty($args)) {
            return $this->__delegate->offsetGet($offset);
        }

        array_unshift($args, $offset);

        return $this->__call('offsetGet', $args);
    }

    /**
     * @param  string|int  $offset
     * @param  mixed|null  ...$args  additional arguments
     * @throws Exception
     */
    public function offsetSet($offset, mixed $value, ...$args): void
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }
        if (empty($args)) {
            $this->__delegate->offsetSet($offset, $value);
        }

        array_unshift($args, $offset, $value);

        $this->__call('offsetSet', $args);
    }

    /**
     * @param  mixed|null  ...$args  additional arguments
     * @throws Exception
     */
    public function offsetUnset(mixed $offset, ...$args) : void
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }
        if (empty($args)) {
            $this->__delegate->offsetUnset($offset);
        }

        array_unshift($args, $offset);

        $this->__call('offsetUnset', $args);
    }

    public function get__java(): int
    {
        return $this->__java;
    }

    /**
     * Return java object id.
     */
    public function __getJavaInternalObjectId(): int
    {
        return $this->__java;
    }

    public function get__signature(): ?string
    {
        return $this->__signature;
    }

    /**
     * The PHP magic method __toString() cannot be applied
     * on the PHP object but has to be delegated to the Java one.
     */
    public function __toString(): string
    {
        if (!isset($this->__delegate)) {
            $this->__createDelegate();
        }

        return $this->__delegate->__toString();
    }

    /**
     * Returns the runtime class of this Object.
     * The returned Class object is the object that is locked by static synchronized methods of the represented class.
     *
     * @return Interfaces\JavaObject Java('java.lang.Object')
     */
    public function getClass(): JavaObject
    {
        return $this->__delegate->getClass();
    }
}
