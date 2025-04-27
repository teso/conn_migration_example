<?php

namespace Package\Database\Connectors;

use Illuminate\Database\Connectors\ConnectionFactory as BaseFactory;
use Illuminate\Database\Connection;
use Package\Database\MySqlConnection;

class ConnectionFactory extends BaseFactory
{
    /**
     * {@inheritdoc}
     *
     * @param  string        $driver
     * @param  \PDO|\Closure $connection
     * @param  string        $database
     * @param  string        $prefix
     * @param  array         $config
     *
     * @return \Illuminate\Database\Connection
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        if ($driver === 'mysql') {
            $connection = new MySqlConnection($connection, $database, $prefix, $config);

            return $connection;
        }

        return parent::createConnection($driver, $connection, $database, $prefix, $config);
    }
}
