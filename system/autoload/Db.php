<?php

class Db
{
    public static function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function envBool($key, $default = false)
    {
        $value = self::env($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function hasEnvironmentConfig()
    {
        foreach (['DATABASE_URL', 'DB_URL', 'MYSQL_URL', 'SERVICE_URI', 'AIVEN_MYSQL_URI'] as $key) {
            if (self::env($key) !== null) {
                return true;
            }
        }

        return self::env('DB_HOST') !== null && self::env('DB_NAME') !== null && self::env('DB_USER') !== null;
    }

    public static function parseConnectionUrl($url)
    {
        if (empty($url)) {
            return [];
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return [];
        }

        $config = [];

        if (!empty($parts['host'])) {
            $config['host'] = $parts['host'];
        }
        if (!empty($parts['port'])) {
            $config['port'] = (string) $parts['port'];
        }
        if (isset($parts['user'])) {
            $config['user'] = urldecode($parts['user']);
        }
        if (isset($parts['pass'])) {
            $config['pass'] = urldecode($parts['pass']);
        }
        if (!empty($parts['path'])) {
            $config['name'] = ltrim(urldecode($parts['path']), '/');
        }

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['charset'])) {
                $config['charset'] = $query['charset'];
            }
            if (!empty($query['ssl-mode'])) {
                $config['ssl_mode'] = $query['ssl-mode'];
            } elseif (!empty($query['ssl_mode'])) {
                $config['ssl_mode'] = $query['ssl_mode'];
            }
            if (!empty($query['ssl-ca'])) {
                $config['ssl_ca'] = $query['ssl-ca'];
            } elseif (!empty($query['ssl_ca'])) {
                $config['ssl_ca'] = $query['ssl_ca'];
            }
        }

        return $config;
    }

    public static function buildConfigFromEnvironment($prefix = 'DB', $fallback = [])
    {
        $prefix = strtoupper($prefix);
        $urlConfig = [];

        $urlKeys = $prefix === 'DB'
            ? ['DATABASE_URL', 'DB_URL', 'MYSQL_URL', 'SERVICE_URI', 'AIVEN_MYSQL_URI']
            : [$prefix . '_DATABASE_URL', $prefix . '_DB_URL'];

        foreach ($urlKeys as $key) {
            $value = self::env($key);
            if ($value !== null) {
                $urlConfig = self::parseConnectionUrl($value);
                break;
            }
        }

        $config = array_merge([
            'host' => 'localhost',
            'port' => '',
            'user' => 'root',
            'pass' => '',
            'name' => 'phpnuxbill',
            'charset' => 'utf8mb4',
            'ssl_mode' => 'DISABLED',
            'ssl_ca' => '',
            'ssl_verify_server_cert' => null,
        ], $fallback, $urlConfig);

        $map = [
            'host' => [$prefix . '_HOST'],
            'port' => [$prefix . '_PORT'],
            'user' => [$prefix . '_USER', $prefix . '_USERNAME'],
            'pass' => [$prefix . '_PASS', $prefix . '_PASSWORD'],
            'name' => [$prefix . '_NAME', $prefix . '_DATABASE'],
            'charset' => [$prefix . '_CHARSET'],
            'ssl_mode' => [$prefix . '_SSL_MODE'],
            'ssl_ca' => [$prefix . '_SSL_CA'],
        ];

        foreach ($map as $configKey => $envKeys) {
            foreach ($envKeys as $envKey) {
                $value = self::env($envKey);
                if ($value !== null) {
                    $config[$configKey] = $value;
                    break;
                }
            }
        }

        $verifyKey = $prefix . '_SSL_VERIFY_SERVER_CERT';
        if (self::env($verifyKey) !== null) {
            $config['ssl_verify_server_cert'] = self::envBool($verifyKey);
        }

        $config['ssl_mode'] = self::normalizeSslMode($config['ssl_mode']);

        return $config;
    }

    public static function buildConfigFromGlobals($prefix = 'db', $fallback = [])
    {
        $config = array_merge([
            'host' => 'localhost',
            'port' => '',
            'user' => 'root',
            'pass' => '',
            'name' => 'phpnuxbill',
            'charset' => 'utf8mb4',
            'ssl_mode' => 'DISABLED',
            'ssl_ca' => '',
            'ssl_verify_server_cert' => null,
        ], $fallback);

        $globalMap = [
            'host' => [$prefix . '_host'],
            'port' => [$prefix . '_port'],
            'user' => [$prefix . '_user'],
            'pass' => [$prefix . '_pass', $prefix . '_password'],
            'name' => [$prefix . '_name'],
            'charset' => [$prefix . '_charset'],
            'ssl_mode' => [$prefix . '_ssl_mode'],
            'ssl_ca' => [$prefix . '_ssl_ca'],
            'ssl_verify_server_cert' => [$prefix . '_ssl_verify_server_cert'],
        ];

        foreach ($globalMap as $configKey => $globalKeys) {
            foreach ($globalKeys as $globalKey) {
                if (isset($GLOBALS[$globalKey]) && $GLOBALS[$globalKey] !== '') {
                    $config[$configKey] = $GLOBALS[$globalKey];
                    break;
                }
            }
        }

        if ($config['ssl_verify_server_cert'] !== null) {
            $config['ssl_verify_server_cert'] = (bool) $config['ssl_verify_server_cert'];
        }
        $config['ssl_mode'] = self::normalizeSslMode($config['ssl_mode']);

        return $config;
    }

    public static function normalizeSslMode($mode)
    {
        $mode = strtoupper(trim((string) $mode));
        if ($mode === '') {
            return 'DISABLED';
        }

        $allowed = ['DISABLED', 'PREFERRED', 'REQUIRED', 'VERIFY_CA', 'VERIFY_IDENTITY'];
        if (!in_array($mode, $allowed, true)) {
            return 'DISABLED';
        }

        return $mode;
    }

    public static function shouldVerifyServerCertificate($config)
    {
        if ($config['ssl_verify_server_cert'] !== null) {
            return (bool) $config['ssl_verify_server_cert'];
        }

        return in_array($config['ssl_mode'], ['VERIFY_CA', 'VERIFY_IDENTITY'], true);
    }

    public static function buildMysqlDsn($config)
    {
        $segments = ['mysql:host=' . $config['host']];

        if (!empty($config['port'])) {
            $segments[] = 'port=' . $config['port'];
        }
        if (!empty($config['name'])) {
            $segments[] = 'dbname=' . $config['name'];
        }
        if (!empty($config['charset'])) {
            $segments[] = 'charset=' . $config['charset'];
        }

        return implode(';', $segments);
    }

    public static function buildPdoOptions($config, $overrides = [])
    {
        $options = [];

        if (!empty($config['charset']) && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['charset'];
        }

        if ($config['ssl_mode'] !== 'DISABLED') {
            if (!empty($config['ssl_ca']) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
            }
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = self::shouldVerifyServerCertificate($config);
            }
        }

        foreach ($overrides as $key => $value) {
            $options[$key] = $value;
        }

        return $options;
    }

    public static function createPdo($config, $overrides = [])
    {
        $options = self::buildPdoOptions($config, $overrides);
        if (!isset($options[PDO::ATTR_ERRMODE])) {
            $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }

        return new PDO(self::buildMysqlDsn($config), $config['user'], $config['pass'], $options);
    }

    public static function configureOrm($config, $connectionName = ORM::DEFAULT_CONNECTION)
    {
        ORM::configure(self::buildMysqlDsn($config), null, $connectionName);
        ORM::configure('username', $config['user'], $connectionName);
        ORM::configure('password', $config['pass'], $connectionName);

        $driverOptions = self::buildPdoOptions($config);
        if (!empty($driverOptions)) {
            ORM::configure('driver_options', $driverOptions, $connectionName);
        }
    }

    public static function syncLegacyPasswordGlobals()
    {
        if (!empty($GLOBALS['db_password']) && empty($GLOBALS['db_pass'])) {
            $GLOBALS['db_pass'] = $GLOBALS['db_password'];
        }
        if (!empty($GLOBALS['db_pass'])) {
            $GLOBALS['db_password'] = $GLOBALS['db_pass'];
        }
        if (!empty($GLOBALS['radius_password']) && empty($GLOBALS['radius_pass'])) {
            $GLOBALS['radius_pass'] = $GLOBALS['radius_password'];
        }
        if (!empty($GLOBALS['radius_pass'])) {
            $GLOBALS['radius_password'] = $GLOBALS['radius_pass'];
        }
    }
}