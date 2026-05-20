<?php

namespace App\Support;

class DatabaseUrlResolver
{
    public static function connectionUrl(?string $dbUrl = null, ?string $databaseUrl = null): ?string
    {
        $dbUrl = trim((string) $dbUrl);

        if ($dbUrl !== '') {
            return $dbUrl;
        }

        $databaseUrl = trim((string) $databaseUrl);

        return $databaseUrl !== '' ? $databaseUrl : null;
    }

    public static function defaultConnection(
        ?string $configuredConnection,
        ?string $dbUrl = null,
        ?string $databaseUrl = null,
        string $fallback = 'sqlite',
    ): string {
        $configuredConnection = trim((string) $configuredConnection);

        if ($configuredConnection !== '') {
            return self::normalizeDriverName($configuredConnection);
        }

        return self::driverFromUrl(self::connectionUrl($dbUrl, $databaseUrl), $fallback);
    }

    public static function connectionUrlForDriver(string $driver, ?string $dbUrl = null, ?string $databaseUrl = null): ?string
    {
        $url = self::connectionUrl($dbUrl, $databaseUrl);

        if ($url === null) {
            return null;
        }

        return self::normalizeDriverName($driver) === self::driverFromUrl($url, '')
            ? $url
            : null;
    }

    public static function driverFromUrl(?string $databaseUrl, string $fallback = 'sqlite'): string
    {
        $scheme = self::urlScheme($databaseUrl);

        if ($scheme === null) {
            return $fallback;
        }

        return match (strtolower(explode('+', $scheme)[0])) {
            'postgres', 'postgresql', 'pgsql' => 'pgsql',
            'mysql' => 'mysql',
            'mariadb' => 'mariadb',
            'sqlite' => 'sqlite',
            'mssql', 'sqlserver', 'sqlsrv' => 'sqlsrv',
            default => $fallback,
        };
    }

    protected static function urlScheme(?string $databaseUrl): ?string
    {
        $databaseUrl = trim((string) $databaseUrl);

        if ($databaseUrl === '') {
            return null;
        }

        $parsedScheme = parse_url($databaseUrl, PHP_URL_SCHEME);

        return is_string($parsedScheme) && $parsedScheme !== '' ? $parsedScheme : null;
    }

    protected static function normalizeDriverName(string $driver): string
    {
        return match (strtolower(trim($driver))) {
            'postgres', 'postgresql' => 'pgsql',
            'mssql', 'sqlserver' => 'sqlsrv',
            default => strtolower(trim($driver)),
        };
    }
}
