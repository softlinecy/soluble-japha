<?php

declare(strict_types=1);

/*
 * Soluble Japha
 *
 * @link      https://github.com/belgattitude/soluble-japha
 * @copyright Copyright (c) 2013-2020 Vanvelthem Sébastien
 * @license   MIT License https://github.com/belgattitude/soluble-japha/blob/master/LICENSE.md
 */

namespace Soluble\Japha\Db;

use Soluble\Japha\Bridge\Adapter;
use Soluble\Japha\Interfaces\JavaObject;
use Soluble\Japha\Bridge\Exception\InvalidArgumentException;
use Soluble\Japha\Bridge\Exception;
use Soluble\Japha\Interfaces;

class DriverManager
{
    /**
     * @var Interfaces\JavaObject DriverManager object ('java.sql.DriverManager')
     */
    protected $driverManager;

    public function __construct(protected Adapter $ba)
    {
    }

    /**
     * Create an sql connection to database.
     *
     *
     * @throws Exception\JavaException
     * @throws Exception\ClassNotFoundException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\BrokenConnectionException
     *
     *
     * @return Interfaces\JavaObject Java('java.sql.Connection')
     */
    public function createConnection(string $dsn, string $driverClass = 'com.mysql.jdbc.Driver'): JavaObject
    {
        if (trim($dsn) === '') {
            $message = 'DSN param must be a valid (on-empty) string';
            throw new InvalidArgumentException(__METHOD__.' '.$message);
        }

        $class = $this->ba->javaClass('java.lang.Class');
        $class->forName($driverClass);

        return $this->getDriverManager()->getConnection($dsn);
    }

    /**
     * Return underlying java driver manager.
     *
     * @return Interfaces\JavaObject Java('java.sql.DriverManager')
     */
    public function getDriverManager(): JavaObject
    {
        if ($this->driverManager === null) {
            $this->driverManager = $this->ba->javaClass('java.sql.DriverManager');
        }

        return $this->driverManager;
    }

    /**
     * Return a JDBC DSN formatted string from options.
     *
     * @param string $driver   driver name  (mysql/mariadb/oracle/postgres...)
     * @param string $db       database name
     * @param string $host     server ip or name
     * @param string $user     username to connect
     * @param string $password password to connect
     * @param array  $options  extra options as an associative array
     */
    public static function getJdbcDsn(string $driver, string $db, string $host, string $user, string $password, array $options = []): string
    {
        $extras = '';
        if (count($options) > 0) {
            $tmp = [];
            foreach ($options as $key => $value) {
                $tmp[] = urlencode($key).'='.urlencode((string) $value);
            }
            
            $extras = '&'.implode('&', $tmp);
        }

        return sprintf('jdbc:%s://%s/%s?user=%s&password=%s', $driver, $host, $db, $user, $password).$extras;
    }
}
