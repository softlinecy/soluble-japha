<?php

declare(strict_types=1);

namespace Soluble\Japha\Bridge\Driver\Pjb62;

use Soluble\Japha\Bridge\Exception\ConnectionException;
use Soluble\Japha\Bridge\Exception\InvalidArgumentException;
use Soluble\Japha\Interfaces\JavaClass;
use Soluble\Japha\Bridge\Exception\InvalidUsageException;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Bridge\Exception\RuntimeException;
use Soluble\Japha\Bridge\Exception\UnexpectedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Soluble\Japha\Bridge\Driver\AbstractDriver;
use Soluble\Japha\Bridge\Driver\ClientInterface;
use Soluble\Japha\Bridge\Driver\Pjb62\Exception\BrokenConnectionException as Pjb62BrokenConnectionException;
use Soluble\Japha\Bridge\Exception\BrokenConnectionException;
use Soluble\Japha\Interfaces;
use Soluble\Japha\Bridge\Exception;

class Pjb62Driver extends AbstractDriver
{
    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var PjbProxyClient
     */
    protected $pjbProxyClient;

    protected ?LoggerInterface $logger;

    /**
     * Constructor.
     *
     * <code>
     *
     * $ba = new Pjb62Driver([
     *     'servlet_address' => 'http://127.0.0.1:8080/javabridge-bundle/servlet.phpjavabridge'
     *      //'use_persistent_connection' => false,
     *      //'java_default_timezone' => null,
     *      //'java_prefer_values' => true,
     *      //'java_log_level' => null,
     *      //'java_send_size' => 8192,
     *      //'java_recv_size' => 8192,
     *      //'internal_encoding' => 'UTF-8',
     *      //'force_simple_xml_parser' => false
     *    ], $logger);
     *
     * </code>
     *
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ConnectionException
     */
    public function __construct(array $options, LoggerInterface $logger = null)
    {
        if (!$logger instanceof LoggerInterface) {
            $logger = new NullLogger();
        }

        $this->logger = $logger;

        try {
            $this->pjbProxyClient = PjbProxyClient::getInstance($options, $this->logger);
        } catch (ConnectionException $e) {
            $address = $options['servlet_address'];
            $msg = sprintf("Cannot connect to php-java-bridge server on '%s', server didn't respond.", $address);
            $this->logger->critical(sprintf('[soluble-japha] %s (', $msg).$e->getMessage().')');
            throw $e;
        } catch (InvalidArgumentException $e) {
            $msg = 'Invalid arguments, cannot initiate connection to java-bridge.';
            $this->logger->error(sprintf('[soluble-japha] %s (', $msg).$e->getMessage().')');
            throw $e;
        }
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Return underlying bridge client.
     *
     * @return PjbProxyClient
     */
    public function getClient(): ClientInterface
    {
        return $this->pjbProxyClient;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BrokenConnectionException
     */
    public function getJavaClass(string $class_name): JavaClass
    {
        try {
            $class = $this->pjbProxyClient->getJavaClass($class_name);
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        return $class;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BrokenConnectionException
     */
    public function instanciate(string $class_name, ...$args): JavaObject
    {
        try {
            $java = new Java($class_name, ...$args);
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        return $java;
    }

    /**
     * Set the java file encoding, for example UTF-8, ISO-8859-1 or ASCII.
     *
     * Needed because php does not support unicode. All string to byte array
     * conversions use this encoding. Example:
     *
     * @param string $encoding Please see Java file.encoding documentation for a list of valid encodings.
     *
     * @throws BrokenConnectionException
     */
    public function setFileEncoding(string $encoding): void
    {
        $this->invoke('setFileEncoding', null, [$encoding]);
    }

    /**
     * Return bridge connection options.
     *
     * @throws BrokenConnectionException
     *
     * @return Interfaces\JavaObject Java("io.soluble.pjb.bridge.Options")
     */
    public function getConnectionOptions(): JavaObject
    {
        return $this->invoke('getOptions', null);
    }

    /**
     * {@inheritdoc}
     *
     * @throws BrokenConnectionException
     */
    public function invoke(string $method, Interfaces\JavaType $javaObject = null, array $args = [])
    {
        try {
            return $this->pjbProxyClient->invokeMethod($method, $javaObject, $args);
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the jsr223 script context handle.
     *
     * @throws BrokenConnectionException
     */
    public function getContext(): JavaObject
    {
        try {
            return $this->pjbProxyClient::getClient()->getContext();
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }

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
     * @throws BrokenConnectionException
     *
     */
    public function getJavaSession(array $args = []): JavaObject
    {
        try {
            return $this->pjbProxyClient::getClient()->getSession();
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Inspect the class internals.
     *
     *
     * @throws BrokenConnectionException
     *
     */
    public function inspect(JavaObject $javaObject): string
    {
        try {
            $inspect = $this->pjbProxyClient->inspect($javaObject);
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        return $inspect;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BrokenConnectionException
     */
    public function isInstanceOf(JavaObject $javaObject, $className): bool
    {
        try {
            return $this->pjbProxyClient->isInstanceOf($javaObject, $className);
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws BrokenConnectionException
     */
    public function values(JavaObject $javaObject)
    {
        try {
            return $this->pjbProxyClient->getValues($javaObject);
        } catch (Pjb62BrokenConnectionException | InvalidUsageException $e) {
            PjbProxyClient::unregisterInstance();
            throw new BrokenConnectionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Return java bridge header or empty string if nothing.
     *
     *
     * @return string header value or empty string if not exists
     */
    public static function getJavaBridgeHeader(string $name, array $array): string
    {
        if (array_key_exists($name, $array)) {
            return $array[$name];
        }
        
        $name = 'HTTP_' . $name;
        if (array_key_exists($name, $array)) {
            return $array[$name];
        }

        return '';
    }

    /**
     * Cast internal objects to a new type.
     *
     * @param Interfaces\JavaObject|JavaType|mixed $javaObject
     *
     * @return mixed
     */
    public static function castPjbInternal($javaObject, string $cast_type)
    {
        if ($javaObject instanceof JavaType) {
            return $javaObject->__cast($cast_type);
        }

        // mixed (string | int | bool)
        $first_char = strtoupper($cast_type[0]);
        return match ($first_char) {
            'S' => (string) $javaObject,
            'B' => (bool) $javaObject,
            'L', 'I' => (int) $javaObject,
            'D', 'F' => (float) $javaObject,
            'N' => null,
            'A' => (array) $javaObject,
            'O' => (object) $javaObject,
            default => null,
        };
    }

    /**
     * {@inheritdoc}
     *
     * @param Interfaces\JavaObject|int|float $javaObject
     */
    public function cast($javaObject, string $cast_type): string|bool|int|float|\stdClass|array|null
    {
        /* @todo see how can it be possible to clean up to new structure
            const CAST_TYPE_STRING  = 'string';
            const CAST_TYPE_BOOLEAN = 'boolean';
            const CAST_TYPE_INTEGER = 'integer';
            const CAST_TYPE_FLOAT   = 'float';
            const CAST_TYPE_ARRAY   = 'array';
            const CAST_TYPE_NULL    = 'null';
            const CAST_TYPE_OBJECT  = 'object';
            const CAST_TYPE_NULL -> null
         */
        $first_char = strtoupper(substr($cast_type, 0, 1));
        return match ($first_char) {
            'S' => (string) $javaObject,
            'B' => (bool) $javaObject,
            'L', 'I' => (int) $javaObject,
            'D', 'F' => (float) $javaObject,
            'N' => null,
            'A' => (array) $javaObject,
            'O' => (object) $javaObject,
            default => throw new RuntimeException('Unsupported cast_type parameter: ' . $cast_type),
        };
    }

    /**
     * Return object java class name.
     *
     * @throws Exception\UnexpectedException
     * @throws BrokenConnectionException
     *
     *
     */
    public function getClassName(JavaObject $javaObject): string
    {
        $inspect = $this->inspect($javaObject);

        // [class java.sql.DriverManager:
        $matches = [];
        preg_match('/^\[class (.+)\:/', $inspect, $matches);
        if (!isset($matches[1]) || $matches[1] === '') {
            throw new UnexpectedException(__METHOD__.' Cannot determine class name');
        }

        return $matches[1];
    }
}
