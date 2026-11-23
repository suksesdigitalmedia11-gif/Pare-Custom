<?php

namespace Tests\Unit;

use App\Database\ResilientConnectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * Penjaga konfigurasi ketahanan yang mudah rusak tanpa terasa.
 */
class SecurityConfigTest extends TestCase
{
    /**
     * Regresi yang pernah mematikan situs: createConnector() di-override
     * sebagai `protected` padahal parent mendeklarasikannya `public`.
     */
    public function test_resilient_connector_create_connector_is_public(): void
    {
        $method = new \ReflectionMethod(ResilientConnectionFactory::class, 'createConnector');

        $this->assertTrue(
            $method->isPublic(),
            'createConnector() harus public agar sesuai signature ConnectionFactory (Laravel 11+)'
        );
    }

    public function test_resilient_connector_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Database\ResilientMySqlConnector::class));
    }
}
