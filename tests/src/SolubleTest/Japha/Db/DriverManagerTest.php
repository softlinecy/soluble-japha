<?php

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace SolubleTest\Japha\Db;

use PHPUnit\Framework\Attributes\Test;
use Soluble\Japha\Bridge\Exception\ClassNotFoundException;
use Soluble\Japha\Bridge\Exception\SqlException;
use Soluble\Japha\Bridge\Exception\InvalidArgumentException;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Bridge\Exception\JavaException;
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Db\DriverManager;
use PHPUnit\Framework\TestCase;

class DriverManagerTest extends TestCase
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
     * @var DriverManager
     */
    protected $driverManager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        if (!$this->isJdbcTestsEnabled()) {
            $this->markTestSkipped(
                'Skipping JDBC mysql driver tests, enable option in phpunit.xml'
            );
        }

        \SolubleTestFactories::startJavaBridgeServer();
        $this->servlet_address = \SolubleTestFactories::getJavaBridgeServerAddress();
        $this->adapter = new Adapter([
            'driver' => 'Pjb62',
            'servlet_address' => $this->servlet_address,
        ]);
        $this->driverManager = new DriverManager($this->adapter);
    }

    protected function isJdbcTestsEnabled(): bool
    {
        return isset($_SERVER['JAPHA_ENABLE_JDBC_TESTS']) &&
            $_SERVER['JAPHA_ENABLE_JDBC_TESTS'] == true;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    #[Test]
    public function createConnectionThrowsClassNotFoundException(): void
    {
        $this->expectException(ClassNotFoundException::class);
        //$this->driverManager->createConnection()
        $config = \SolubleTestFactories::getDatabaseConfig();
        $host = $config['hostname'];
        $db = $config['database'];
        $user = $config['username'];
        $password = $config['password'];
        $dsn = sprintf('jdbc:mysql://%s/%s?user=%s&password=%s', $host, $db, $user, $password);
        $this->driverManager->createConnection($dsn, 'com.nuvolia.jdbc.JDBC4Connection');
    }

    #[Test]
    public function createConnectionThrowsSqlException(): void
    {
        $this->expectException(SqlException::class);
        //$this->driverManager->createConnection()
        $config = \SolubleTestFactories::getDatabaseConfig();
        $host = $config['hostname'];
        $db = $config['database'];
        $user = $config['username'];
        $password = $config['password'];
        $dsn = sprintf('jdbc:invaliddbdriver://%s/%s?user=%s&password=%s', $host, $db, $user, $password);
        $this->driverManager->createConnection($dsn, 'com.mysql.jdbc.Driver');
    }

    #[Test]
    public function createConnectionThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dsn = '';
        $this->driverManager->createConnection($dsn, 'com.nuvolia.jdbc.JDBC4Connection');
    }

    #[Test]
    public function getDriverManager(): void
    {
        $dm = $this->driverManager->getDriverManager();
        $this->assertInstanceOf(JavaObject::class, $dm);
        $className = $this->adapter->getDriver()->getClassName($dm);
        $this->assertSame('java.sql.DriverManager', $className);
        //self::assertTrue($this->adapter->isInstanceOf($dm, 'java.sql.DriverManager'));
    }

    #[Test]
    public function createConnection(): void
    {
        $dsn = $this->getWorkingDSN();
        $conn = null;
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $exception) {
            $this->assertFalse(true, 'Cannot connect: '.$exception->getMessage());
        }
        
        $className = $this->adapter->getDriver()->getClassName($conn);
        $this->assertContains($className, ['com.mysql.jdbc.JDBC4Connection', 'com.mysql.cj.jdbc.ConnectionImpl']);
        $conn->close();
    }

    #[Test]
    public function statement(): void
    {
        $dsn = $this->getWorkingDSN();
        $conn = null;
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $exception) {
            $this->assertFalse(true, 'Cannot connect: '.$exception->getMessage());
        }

        $stmt = $conn->createStatement();
        $rs = $stmt->executeQuery('select * from product_category_translation limit 100');
        while ($rs->next()) {
            $category_id = $rs->getString('category_id');
            $this->assertIsNumeric($category_id->__toString());
        }
        
        $ba = $this->adapter;
        if (!$ba->isNull($rs)) {
            $rs->close();
        }
        
        if (!$ba->isNull($stmt)) {
            $stmt->close();
        }
        
        $conn->close();
    }

    #[Test]
    public function invalidQueryThrowsException(): void
    {
        $this->expectException(JavaException::class);
        $dsn = $this->getWorkingDSN();
        $conn = null;
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $exception) {
            $this->assertFalse(true, 'Cannot connect: '.$exception->getMessage());
        }

        $stmt = $conn->createStatement();
        try {
            $rs = $stmt->executeQuery('select * from non_existing_table limit 100');
            $this->assertTrue(false, 'Error: a JavaException exception was expected');
        } catch (JavaException $javaException) {
            $this->assertTrue(true, 'Exception have been thrown');
            $java_cls = $javaException->getJavaClassName();
            $this->assertContains($java_cls, [
                'com.mysql.jdbc.exceptions.jdbc4.MySQLSyntaxErrorException',
                'java.sql.SQLSyntaxErrorException'
            ]);
            $conn->close();
            throw $javaException;
        }
    }

    #[Test]
    public function getJdbcDSN(): void
    {
        $dsn = DriverManager::getJdbcDsn('mysql', 'db', 'host', 'user', 'password', []);
        $this->assertSame('jdbc:mysql://host/db?user=user&password=password', $dsn);
    }

    #[Test]
    public function getJdbcDSNWithExtras(): void
    {
        $extras = [
          'param1' => 'Hello',
          'param2' => 'éà&AA'
        ];
        $dsn = DriverManager::getJdbcDsn('mysql', 'db', 'host', 'user', 'password', $extras);
        $expected = 'jdbc:mysql://host/db?user=user&password=password&param1=Hello&param2=%C3%A9%C3%A0%26AA';
        $this->assertSame($expected, $dsn);
    }

    protected function getWorkingDSN(): string
    {
        $config = \SolubleTestFactories::getDatabaseConfig();
        $host = $config['hostname'];
        $db = $config['database'];
        $user = $config['username'];
        $password = $config['password'];
        $serverTimezone = urlencode('GMT+1');

        return sprintf('jdbc:mysql://%s/%s?user=%s&password=%s&serverTimezone=%s', $host, $db, $user, $password, $serverTimezone);
    }
}
