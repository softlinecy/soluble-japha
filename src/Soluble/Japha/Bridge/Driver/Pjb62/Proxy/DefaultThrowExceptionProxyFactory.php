<?php

declare(strict_types=1);

namespace Soluble\Japha\Bridge\Driver\Pjb62\Proxy;

use Soluble\Japha\Bridge\Driver\Pjb62\ThrowExceptionProxyFactory;
use Soluble\Japha\Bridge\Driver\Pjb62\Exception\JavaException;
use Soluble\Japha\Bridge\Exception\JavaExceptionInterface;
use Psr\Log\LoggerInterface;
use Soluble\Japha\Bridge\Exception;
use Soluble\Japha\Bridge\Driver\Pjb62\Client;

class DefaultThrowExceptionProxyFactory extends ThrowExceptionProxyFactory
{

    protected string $defaultException = 'JavaException';

    protected  array $msgPatternsMapping = [
        'NoSuchMethodException' => '/(php.java.bridge.NoSuchProcedureException)|(Cause: java.lang.NoSuchMethodException)/',
        'ClassNotFoundException' => '/Cause: java.lang.ClassNotFoundException/',
        //'InvalidArgumentException' => '/^Invoke failed(.*)php.java.bridge.NoSuchProcedureException/',
        'SqlException' => '/^Invoke failed(.*)java.sql.SQLException/',
        'NoSuchFieldException' => '/Cause: java.lang.NoSuchFieldException/',
        //'NullPointerException' => '/Cause: java.lang.NullPointerException/'
    ];

    public function __construct(Client $client, protected LoggerInterface $logger)
    {
        parent::__construct($client);
    }

    /**
     * @throws Exception\JavaExceptionInterface
     */
    public function checkResult(JavaException $result): void
    {
        throw $this->getExceptionFromResult($result);
    }

    
    private function getExceptionFromResult(JavaException $result): JavaExceptionInterface
    {
        $message = (string) $result->__get('message')->__toString();

        $exceptionClass = $this->defaultException;

        foreach ($this->msgPatternsMapping as $key => $pattern) {
            if (preg_match($pattern, $message)) {
                $exceptionClass = (string) $key;
                break;
            }
        }

        $cls = '\\Soluble\\Japha\\Bridge\\Exception\\'.$exceptionClass;

        // Public message, mask any login/passwords
        $message = preg_replace('/user=([^&\ ]+)|password=([^&\ ]+)/', '****', $message);
        $stackTrace = $result->getCause()->__toString();
        $code = $result->getCode();
        $driverException = $result;

        // Getting original class name from cause
        preg_match('/Cause: ([^:]+):/', (string) $message, $matches);
        $javaExceptionClass = count($matches) > 1 ? $matches[1] : 'Unknown java exception class';

        // Getting cause from message
        $tmp = explode('Cause: ', (string) $message);
        if (count($tmp) > 1) {
            array_shift($tmp);
            $cause = trim(implode(', ', $tmp));
        } else {
            $cause = $message;
        }
        
        $e = new $cls(
            $message,
            $cause,
            $stackTrace,
            $javaExceptionClass,
            $code,
            $driverException,
            null
        );

        $this->logException($e, $exceptionClass);

        return $e;
    }

    private function logException(\Throwable $e, string $exceptionClass): void
    {
        $this->logger->error(sprintf(
            '[soluble-japha] Encountered exception %s: %s, code %s (%s)',
            $exceptionClass,
            $e->getMessage(),
            $e->getCode() ?? '?',
            $e::class
        ));
    }
}
