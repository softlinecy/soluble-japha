<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Bridge\Driver;

use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Bridge\Driver\DriverInterface;
use Soluble\Japha\Bridge\Exception\JavaException;
use Soluble\Japha\Interfaces\JavaObject;
use PHPUnit\Framework\TestCase;

class DriverSessionTest extends TestCase
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
     * @var DriverInterface
     */
    protected $driver;

    protected function setUp(): void
    {
        \SolubleTestFactories::startJavaBridgeServer();
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
        $this->adapter = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ]);
        $this->driver = $this->adapter->getDriver();
        //$this->markTestSkipped('Not yet implemented');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    #[Test]
    public function javaSessionType(): void
    {
        try {
            $session = $this->driver->getJavaSession();
            $this->assertInstanceOf(JavaObject::class, $session);
        } catch (JavaException $javaException) {
            $cls = $javaException->getJavaClassName();

            if ($cls === 'java.lang.IllegalStateException') {
                $this->markTestSkipped('Skipped session test: Probably under tomcat -> Cannot create a session after the response has been committed');
            } else {
                $this->assertTrue(false, sprintf('Cannot test session type: (%s)', $cls));
            }
        }
    }

    #[Test]
    public function javaSession(): void
    {
        try {
            $session = $this->adapter->getDriver()->getJavaSession();

            $counter = $session->get('counter');
            if ($this->adapter->isNull($counter)) {
                $session->put('counter', 1);
            } else {
                $session->put('counter', $counter + 1);
            }
        } catch (JavaException $javaException) {
            $cls = $javaException->getJavaClassName();
            if ($cls === 'java.lang.IllegalStateException') {
                $this->markTestSkipped('Skipped session test: Probably under tomcat -> Cannot create a session after the response has been committed');
            } else {
                $this->assertTrue(false, sprintf('Cannot test session type: (%s)', $cls));
            }
        }
    }
}
