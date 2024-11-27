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
 * @method string getBufferContents()
 */

namespace Soluble\Japha\Bridge\Driver\Pjb62;

use Soluble\Japha\Bridge\Driver\Pjb62\Exception\IllegalArgumentException;
use Soluble\Japha\Bridge\Driver\Pjb62\Exception\IllegalStateException;

class Java extends AbstractJava
{
    /**
     * @var string
     */
    protected $__internal_encoding;

    /**
     * Java constructor.
     *
     * @param string     $name    Java FQCN or an array with JavaFQCN followed by params
     * @param mixed|null ...$args variadic parameters
     */
    public function __construct(string $name, ...$args)
    {
        $this->__client = PjbProxyClient::getInstance()->getClient();
        $this->__internal_encoding = $this->__client->getParam(Client::PARAM_JAVA_INTERNAL_ENCODING);

        $client = $this->__client;

        $sig = sprintf('&%s@%s', $this->__signature, $name);
        $len = count($args);
        $args2 = [];
        for ($i = 0; $i < $len; ++$i) {
            $val = $args[$i];
            switch (gettype($val)) {
                case 'boolean':
                    $args2[] = $val;
                    $sig .= '@b';
                    break;
                case 'integer':
                    $args2[] = $val;
                    $sig .= '@i';
                    break;
                case 'double':
                    $args2[] = $val;
                    $sig .= '@d';
                    break;
                case 'string':
                    $args2[] = htmlspecialchars($val, ENT_COMPAT, $this->__internal_encoding);
                    $sig .= '@s';
                    break;
                case 'array':
                    $sig = '~INVALID';
                    break;
                case 'object':
                    if ($val instanceof JavaType) {
                        $args2[] = $val->get__java();
                        $sig .= '@o' . $val->get__signature();
                    } else {
                        $sig = '~INVALID';
                    }
                    
                    break;
                case 'resource':
                    $args2[] = $val;
                    $sig .= '@r';
                    break;
                case 'NULL':
                    $args2[] = $val;
                    $sig .= '@N';
                    break;
                case 'unknown type':
                    $args2[] = $val;
                    $sig .= '@u';
                    break;
                default:
                    throw new IllegalArgumentException($val);
            }
        }

        if (array_key_exists($sig, $client->methodCache)) {
            $cacheEntry = &$client->methodCache[$sig];
            $client->sendBuffer .= $client->preparedToSendBuffer;
            //if (strlen($client->sendBuffer) >= JAVA_SEND_SIZE) {
            if (strlen($client->sendBuffer) >= $this->__client->java_send_size) {
                if ($client->protocol->handler->write($client->sendBuffer) <= 0) {
                    throw new IllegalStateException('Connection out of sync,check backend log for details.');
                }
                
                $client->sendBuffer = null;
            }
            
            $client->preparedToSendBuffer = vsprintf($cacheEntry->fmt, $args2);
            $this->__java = ++$client->asyncCtx;

            $this->__factory = $cacheEntry->factory;
            $this->__signature = $cacheEntry->signature;
            $this->__cancelProxyCreationTag = ++$client->cancelProxyCreationTag;
        } else {
            $client->currentCacheKey = $sig;
            $this->__delegate = $client->createObject($name, $args);
            $delegate = $this->__delegate;

            $this->__java = $delegate->get__java();
            $this->__signature = $delegate->get__signature();
        }
    }

    public function __destruct()
    {
        if (!isset($this->__client)) {
            return;
        }
        
        $client = $this->__client;
        $preparedToSendBuffer = &$client->preparedToSendBuffer;
        if ($preparedToSendBuffer &&
                $client->cancelProxyCreationTag == $this->__cancelProxyCreationTag) {
            $preparedToSendBuffer[6] = '3';
            $client->sendBuffer .= $preparedToSendBuffer;
            $preparedToSendBuffer = null;
            --$client->asyncCtx;
        } elseif (!isset($this->__delegate)) {
            $client->unref($this->__java);
        }
    }

    /**
     * Call a method on this JavaObject.
     *
     *
     * @return mixed|null
     */
    public function __call(string $name, array $arguments)
    {
        $client = $this->__client;
        $sig = sprintf('@%s@%s', $this->__signature, $name);
        $len = count($arguments);
        $args2 = [$this->__java];
        for ($i = 0; $i < $len; ++$i) {
            switch (gettype($val = $arguments[$i])) {
                case 'boolean':
                    $args2[] = $val;
                    $sig .= '@b';
                    break;
                case 'integer':
                    $args2[] = $val;
                    $sig .= '@i';
                    break;
                case 'double':
                    $args2[] = $val;
                    $sig .= '@d';
                    break;
                case 'string':
                    $args2[] = htmlspecialchars($val, ENT_COMPAT, $this->__internal_encoding);
                    $sig .= '@s';
                    break;
                case 'array':
                    $sig = '~INVALID';
                    break;
                case 'object':
                    if ($val instanceof JavaType) {
                        $args2[] = $val->get__java();
                        $sig .= '@o' . $val->get__signature();
                    } else {
                        $sig = '~INVALID';
                    }
                    
                    break;
                case 'resource':
                    $args2[] = $val;
                    $sig .= '@r';
                    break;
                case 'NULL':
                    $args2[] = $val;
                    $sig .= '@N';
                    break;
                case 'unknown type':
                    $args2[] = $val;
                    $sig .= '@u';
                    break;
                default:
                    throw new IllegalArgumentException($val);
            }
        }
        
        if (array_key_exists($sig, $client->methodCache)) {
            $cacheEntry = &$client->methodCache[$sig];
            $client->sendBuffer .= $client->preparedToSendBuffer;
            if (strlen($client->sendBuffer) >= $this->__client->java_send_size) {
                if ($client->protocol->handler->write($client->sendBuffer) <= 0) {
                    throw new IllegalStateException('Out of sync. Check backend log for details.');
                }
                
                $client->sendBuffer = null;
            }
            
            $client->preparedToSendBuffer = vsprintf($cacheEntry->fmt, $args2);
            if ($cacheEntry->resultVoid) {
                ++$client->cancelProxyCreationTag;

                return null;
            }
            $result = clone $client->cachedJavaPrototype;
            $result->__factory = $cacheEntry->factory;
            $result->__java = ++$client->asyncCtx;
            $result->__signature = $cacheEntry->signature;
            $result->__cancelProxyCreationTag = ++$client->cancelProxyCreationTag;
            return $result;
        }
        $client->currentCacheKey = $sig;
        return parent::__call($name, $arguments);
    }
}
