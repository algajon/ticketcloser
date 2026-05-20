<?php

namespace Tests\Unit;

use App\Support\DatabaseUrlResolver;
use PHPUnit\Framework\TestCase;

class DatabaseUrlResolverTest extends TestCase
{
    public function test_connection_url_prefers_db_url_over_database_url(): void
    {
        $url = DatabaseUrlResolver::connectionUrl(
            'mysql://root:secret@127.0.0.1:3306/app',
            'postgres://postgres:secret@127.0.0.1:5432/app',
        );

        $this->assertSame('mysql://root:secret@127.0.0.1:3306/app', $url);
    }

    public function test_default_connection_uses_database_url_when_db_connection_is_missing(): void
    {
        $driver = DatabaseUrlResolver::defaultConnection(
            null,
            null,
            'postgres://postgres:secret@127.0.0.1:5432/app',
        );

        $this->assertSame('pgsql', $driver);
    }

    public function test_default_connection_normalizes_common_driver_aliases(): void
    {
        $this->assertSame('pgsql', DatabaseUrlResolver::defaultConnection('postgres'));
        $this->assertSame('sqlsrv', DatabaseUrlResolver::defaultConnection('sqlserver'));
    }

    public function test_connection_url_for_driver_only_returns_matching_url(): void
    {
        $url = 'postgres://postgres:secret@127.0.0.1:5432/app';

        $this->assertSame($url, DatabaseUrlResolver::connectionUrlForDriver('pgsql', null, $url));
        $this->assertNull(DatabaseUrlResolver::connectionUrlForDriver('mysql', null, $url));
    }

    public function test_driver_from_url_supports_render_and_sql_server_schemes(): void
    {
        $this->assertSame('pgsql', DatabaseUrlResolver::driverFromUrl('postgresql://postgres:secret@127.0.0.1:5432/app'));
        $this->assertSame('sqlsrv', DatabaseUrlResolver::driverFromUrl('sqlserver://sa:secret@127.0.0.1:1433/app'));
    }
}
