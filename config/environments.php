<?php
// Environment configuration
// Location: /config/environments.php

class Environment {
    private static $environment = null;
    private static $config = null;
    
    // Define available environments
    const LOCAL = 'local';
    const STAGING = 'staging';
    const PRODUCTION = 'production';
    
    // Detect current environment
    public static function detect() {
        if (self::$environment !== null) {
            return self::$environment;
        }
        
        // Check for environment file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'APP_ENV=') === 0) {
                    self::$environment = trim(explode('=', $line, 2)[1]);
                    return self::$environment;
                }
            }
        }
        
        // Auto-detect based on server
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            self::$environment = self::LOCAL;
        } elseif (strpos($host, 'staging.') !== false || strpos($host, 'test.') !== false) {
            self::$environment = self::STAGING;
        } else {
            self::$environment = self::PRODUCTION;
        }
        
        return self::$environment;
    }
    
    // Get environment-specific configuration
    public static function getConfig() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        $env = self::detect();
        
        switch ($env) {
            case self::LOCAL:
                self::$config = [
                    'name' => 'Local Development',
                    'debug' => true,
                    'error_reporting' => E_ALL,
                    'display_errors' => true,
                    'db_host' => 'localhost',
                    'db_user' => 'root',
                    'db_password' => '',
                    'db_name' => 'umojawetu_db',
                    'site_url' => 'http://localhost/umojawetu-platform',
                    'stripe_test_mode' => true,
                    'email_log_only' => true,
                    'force_https' => false,
                ];
                break;
                
            case self::STAGING:
                self::$config = [
                    'name' => 'Staging Server',
                    'debug' => true,
                    'error_reporting' => E_ALL,
                    'display_errors' => true,
                    'db_host' => 'localhost',
                    'db_user' => 'staging_user',
                    'db_password' => 'staging_password',
                    'db_name' => 'umojawetu_staging',
                    'site_url' => 'https://staging.yourdomain.com',
                    'stripe_test_mode' => true,
                    'email_log_only' => true,
                    'force_https' => true,
                ];
                break;
                
            case self::PRODUCTION:
                self::$config = [
                    'name' => 'Production Server',
                    'debug' => false,
                    'error_reporting' => 0,
                    'display_errors' => false,
                    'db_host' => 'localhost',
                    'db_user' => 'prod_user',
                    'db_password' => 'prod_password',
                    'db_name' => 'umojawetu_prod',
                    'site_url' => 'https://www.yourdomain.com',
                    'stripe_test_mode' => false,
                    'email_log_only' => false,
                    'force_https' => true,
                ];
                break;
        }
        
        return self::$config;
    }
    
    // Helper methods
    public static function isLocal() {
        return self::detect() === self::LOCAL;
    }
    
    public static function isStaging() {
        return self::detect() === self::STAGING;
    }
    
    public static function isProduction() {
        return self::detect() === self::PRODUCTION;
    }
    
    public static function isDebug() {
        return self::getConfig()['debug'] === true;
    }
}
?>