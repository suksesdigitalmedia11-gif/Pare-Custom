<?php

namespace App\Database;

use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * Connection factory yang memakai {@see ResilientMySqlConnector} untuk driver
 * MySQL, sehingga koneksi database otomatis di-retry saat server penuh.
 */
class ResilientConnectionFactory extends ConnectionFactory
{
    /**
     * @param  array<string, mixed>  $config
     * @return \Illuminate\Database\Connectors\ConnectorInterface
     */
    public function createConnector(array $config)
    {
        if (($config['driver'] ?? null) === 'mysql') {
            return new ResilientMySqlConnector;
        }

        return parent::createConnector($config);
    }
}
