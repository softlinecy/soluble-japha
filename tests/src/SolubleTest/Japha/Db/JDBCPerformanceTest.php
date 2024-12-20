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
use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Db\DriverManager;
use PHPUnit\Framework\TestCase;

class JDBCPerformanceTest extends TestCase
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
                'Skipping JDBC mysql performance tests, enable option in phpunit.xml'
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

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    #[Test]
    public function statementWithGetValuesOptimization(): void
    {
        $ba = $this->adapter;
        $dsn = $this->getPHPUnitJdbcDSN(); // "jdbc:mysql://$host/$db?user=$user&password=$password"
        $conn = null;
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $exception) {
            $this->assertFalse(true, 'Cannot connect: '.$exception->getMessage());
        }

        $stmt = $conn->createStatement();
        $rs = $stmt->executeQuery('select * from product_category_translation limit 1000');

        $list = $ba->java('java.util.LinkedList');  // 1 round trip (ArrayList is possible)

        while ($rs->next()) {
            $list->add($rs->getString('title')); // Data is set in the JVM only
        }

        $titles = (array) $ba->getDriver()->values($list); // 1 round trip

        $this->assertContains('Jack', $titles);

        if (!$ba->isNull($rs)) {
            $rs->close();
        }
        
        if (!$ba->isNull($stmt)) {
            $stmt->close();
        }

        $conn->close();
    }

    #[Test]
    public function statementWithMapAndGetValuesOptimization(): void
    {
        $ba = $this->adapter;
        $dsn = $this->getPHPUnitJdbcDSN(); // "jdbc:mysql://$host/$db?user=$user&password=$password"
        $conn = null;
        try {
            $conn = $this->driverManager->createConnection($dsn);
        } catch (\Exception $exception) {
            $this->assertFalse(true, 'Cannot connect: '.$exception->getMessage());
        }

        $stmt = $conn->createStatement();
        $rs = $stmt->executeQuery('select * from product_category limit 1000');

        $list = $ba->java('java.util.HashMap');  // 1 round trip

        while ($rs->next()) {
            $list->put($rs->getString('reference'), $rs->getString('title')); // Data is set in the JVM only
        }

        $titles = (array) $ba->getDriver()->values($list); // 1 round trip

        $this->assertContains('Accessoires', $titles);
        $this->assertArrayHasKey('PIAC', $titles);
        $this->assertSame('Accessoires', $titles['PIAC']);

        if (!$ba->isNull($rs)) {
            $rs->close();
        }
        
        if (!$ba->isNull($stmt)) {
            $stmt->close();
        }

        $conn->close();
    }

    protected function getPHPUnitJdbcDSN(): string
    {
        $config = \SolubleTestFactories::getDatabaseConfig();
        $host = $config['hostname'];
        $db = $config['database'];
        $user = $config['username'];
        $password = $config['password'];
        $serverTimezone = urlencode('GMT+1');

        return sprintf('jdbc:mysql://%s/%s?user=%s&password=%s&serverTimezone=%s', $host, $db, $user, $password, $serverTimezone);
    }

    protected function isJdbcTestsEnabled(): bool
    {
        return isset($_SERVER['JAPHA_ENABLE_JDBC_TESTS']) &&
            $_SERVER['JAPHA_ENABLE_JDBC_TESTS'] == true;
    }
}
