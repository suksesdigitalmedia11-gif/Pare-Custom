<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\Facades\Log;
use PDOException;

/**
 * MySQL connector dengan retry otomatis saat koneksi gagal karena kondisi
 * sementara (mis. server MySQL penuh: "Too many connections").
 *
 * Kenapa aman: retry hanya terjadi pada tahap PEMBUKAAN koneksi. Query belum
 * pernah dieksekusi, sehingga tidak ada risiko double-write / race condition.
 * Retry TIDAK dilakukan untuk error query (mis. duplicate key) karena itu
 * bukan kondisi sementara.
 *
 * Konfigurasi (config/database.php → connections.mysql):
 *   'retry_attempts' => env('DB_RETRY_ATTEMPTS', 3),
 *   'retry_delay_ms' => env('DB_RETRY_DELAY_MS', 200),
 */
class ResilientMySqlConnector extends MySqlConnector
{
    /**
     * SQLSTATE kelas 08 = connection exception (koneksi gagal dibuka).
     */
    private const TRANSIENT_SQLSTATES = ['08000', '08001', '08004', '08006'];

    /**
     * Kode driver MySQL yang menandakan kegagalan pembukaan koneksi:
     * 1040 too many connections, 1203 max user connections,
     * 2002/2003 cannot connect, 2013 lost connection saat handshake.
     */
    private const TRANSIENT_DRIVER_CODES = [1040, 1203, 2002, 2003, 2013];

    /**
     * @param  array<string, mixed>  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $attempts = max(1, (int) ($config['retry_attempts'] ?? 3));
        $delayMs = max(0, (int) ($config['retry_delay_ms'] ?? 200));

        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return parent::connect($config);
            } catch (PDOException $e) {
                if ($attempt >= $attempts || ! $this->isTransientConnectionError($e)) {
                    throw $e;
                }

                Log::warning('Database connection retry', [
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                    'sqlstate' => (string) $e->getCode(),
                    'driver_code' => $e->errorInfo[1] ?? null,
                    'message' => $e->getMessage(),
                ]);

                // Backoff linear: 200ms, 400ms, 600ms, ...
                usleep($delayMs * 1000 * $attempt);
            }
        }
    }

    private function isTransientConnectionError(PDOException $e): bool
    {
        $sqlState = (string) $e->getCode();
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return in_array($sqlState, self::TRANSIENT_SQLSTATES, true)
            || in_array($driverCode, self::TRANSIENT_DRIVER_CODES, true);
    }
}
