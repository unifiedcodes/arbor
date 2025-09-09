<?php

namespace Arbor\auth;

use Exception;

/**
 * SSL Key Pair Generator
 * 
 * This class handles the generation of RSA public/private key pairs for SSL/TLS operations.
 * It provides automatic OpenSSL configuration detection across different platforms and 
 * environments (Windows XAMPP/WAMP/MAMP, Linux, macOS).
 * 
 * Features:
 * - Automatic OpenSSL configuration file detection
 * - Cross-platform compatibility (Windows, Linux, macOS)
 * - Configurable key size (default 2048 bits)
 * - Proper file permissions setting
 * - Comprehensive error handling with detailed OpenSSL error reporting
 * 
 * @package Arbor\auth
 */
class SslKeysGenerator
{
    /**
     * RSA key size in bits
     * 
     * @var int Default is 2048 bits for good security/performance balance
     */
    private int $keySize = 2048;

    /**
     * File system path where the private key will be saved
     * 
     * @var string Absolute or relative path to private key file
     */
    private string $privateKeyPath;

    /**
     * File system path where the public key will be saved
     * 
     * @var string Absolute or relative path to public key file
     */
    private string $publicKeyPath;

    /**
     * Path to OpenSSL configuration file
     * 
     * @var string|null Null if no config file is found or specified
     */
    private ?string $opensslConfigPath = null;

    /**
     * Constructor - Initialize the SSL key generator
     * 
     * @param string $privateKeyPath Path where private key will be saved
     * @param string $publicKeyPath Path where public key will be saved
     * @param int $keySize RSA key size in bits (default: 2048)
     * @param string|null $opensslConfigPath Custom OpenSSL config path (auto-detected if null)
     * 
     * @throws Exception If paths are invalid or inaccessible
     */
    public function __construct(string $privateKeyPath, string $publicKeyPath, int $keySize = 2048, ?string $opensslConfigPath = null)
    {
        $this->privateKeyPath = $privateKeyPath;
        $this->publicKeyPath = $publicKeyPath;
        $this->keySize = $keySize;
        $this->opensslConfigPath = $opensslConfigPath ?? $this->detectDefaultConfig();
    }

    /**
     * Generate and save RSA key pair to specified file paths
     * 
     * This method performs the complete key generation workflow:
     * 1. Creates necessary directories
     * 2. Clears OpenSSL error queue
     * 3. Sets up OpenSSL configuration
     * 4. Generates RSA key pair
     * 5. Exports private key
     * 6. Extracts public key
     * 7. Saves both keys with appropriate file permissions
     * 
     * @return void
     * @throws Exception If key generation fails at any step
     */
    public function generate(): void
    {
        // Ensure the directories exist
        $this->ensureDirectoryExists(dirname($this->privateKeyPath));
        $this->ensureDirectoryExists(dirname($this->publicKeyPath));

        // Clear any existing OpenSSL errors
        while (openssl_error_string()) {
            // Clear error queue
        }

        // Set OpenSSL configuration if found
        if ($this->opensslConfigPath && file_exists($this->opensslConfigPath)) {
            putenv("OPENSSL_CONF={$this->opensslConfigPath}");
        }

        $config = [
            "private_key_bits" => $this->keySize,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Add config file to the configuration array if available
        if ($this->opensslConfigPath && file_exists($this->opensslConfigPath)) {
            $config['config'] = $this->opensslConfigPath;
        }

        // Try generating without config first if no config file found
        if (!$this->opensslConfigPath || !file_exists($this->opensslConfigPath)) {
            $res = $this->generateWithoutConfig();
        } else {
            $res = openssl_pkey_new($config);
        }

        if (!$res) {
            $errors = $this->collectOpenSSLErrors();
            throw new Exception("Failed to generate keys:\n" . $errors);
        }

        if (!openssl_pkey_export($res, $privateKey, null, $config)) {
            $errors = $this->collectOpenSSLErrors();
            throw new Exception("Failed to export private key:\n" . $errors);
        }

        $details = openssl_pkey_get_details($res);

        if (!$details || !isset($details['key'])) {
            throw new Exception("Failed to extract public key details.");
        }

        $publicKey = $details['key'];

        $this->saveKey($this->privateKeyPath, $privateKey, 0600);
        $this->saveKey($this->publicKeyPath, $publicKey, 0644);
    }

    /**
     * Generate RSA key pair without OpenSSL configuration file
     * 
     * This method temporarily unsets the OPENSSL_CONF environment variable
     * to allow key generation when no valid configuration file is available.
     * Falls back to minimal OpenSSL configuration.
     * 
     * @return resource|false OpenSSL key resource on success, false on failure
     */
    private function generateWithoutConfig()
    {
        // Try to generate without config file by using minimal configuration
        $config = [
            "private_key_bits" => $this->keySize,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Unset the OPENSSL_CONF environment variable temporarily
        $originalConf = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF');

        $res = openssl_pkey_new($config);

        // Restore original config if it existed
        if ($originalConf !== false) {
            putenv("OPENSSL_CONF={$originalConf}");
        }

        return $res;
    }

    /**
     * Ensure a directory exists, create it if necessary
     * 
     * Creates directory structure recursively with 0755 permissions.
     * 
     * @param string $directory Directory path to create
     * @return void
     * @throws Exception If directory cannot be created
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Failed to create directory: {$directory}");
            }
        }
    }

    /**
     * Save cryptographic key to file with specified permissions
     * 
     * @param string $path File path where key will be saved
     * @param string $key Key content (PEM format)
     * @param int $permissions Octal file permissions (e.g., 0600 for private, 0644 for public)
     * @return void
     * @throws Exception If file cannot be written
     */
    private function saveKey(string $path, string $key, int $permissions): void
    {
        if (file_put_contents($path, $key) === false) {
            throw new Exception("Failed to save key to {$path}");
        }
        chmod($path, $permissions);
    }

    /**
     * Auto-detect OpenSSL configuration file location
     * 
     * Searches for OpenSSL configuration files in common locations across different platforms:
     * - Environment variable (OPENSSL_CONF)
     * - Windows development environments (XAMPP, WAMP, MAMP)
     * - Windows system installations
     * - Linux/Unix standard locations
     * - macOS system and package manager locations
     * - Command line tool detection (where/which openssl)
     * 
     * @return string|null Path to OpenSSL config file, null if not found
     */
    private function detectDefaultConfig(): ?string
    {
        // First, try to get from environment
        $envConfig = getenv('OPENSSL_CONF');

        if ($envConfig && file_exists($envConfig)) {
            return $envConfig;
        }

        // Common locations on Windows (XAMPP/WAMP/MAMP) or Linux
        $possiblePaths = [
            // Windows XAMPP
            'C:\\xampp\\apache\\bin\\openssl.cnf',
            'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
            'C:\\xampp\\apache\\conf\\openssl.cnf',

            // Windows WAMP
            'C:\\wamp64\\bin\\apache\\apache2.4.41\\bin\\openssl.cnf',
            'C:\\wamp\\bin\\apache\\apache2.4.41\\bin\\openssl.cnf',

            // Windows MAMP
            'C:\\MAMP\\bin\\apache\\conf\\openssl.cnf',

            // Windows system paths
            'C:\\Program Files\\OpenSSL-Win64\\bin\\openssl.cfg',
            'C:\\OpenSSL-Win64\\bin\\openssl.cfg',
            'C:\\OpenSSL\\bin\\openssl.cnf',

            // Linux/Unix paths
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
            '/System/Library/OpenSSL/openssl.cnf', // macOS
            '/opt/local/etc/openssl/openssl.cnf',  // MacPorts
            '/usr/local/etc/openssl/openssl.cnf',  // Homebrew
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to find it using where/which command
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('where openssl 2>nul');
        } else {
            $output = shell_exec('which openssl 2>/dev/null');
        }

        if ($output && trim($output)) {
            $opensslPath = trim($output);
            $configPath = dirname($opensslPath) . DIRECTORY_SEPARATOR . 'openssl.cnf';
            if (file_exists($configPath)) {
                return $configPath;
            }
        }

        return null; // Return null instead of throwing exception
    }

    /**
     * Collect all OpenSSL error messages from the error queue
     * 
     * OpenSSL maintains an internal error queue that accumulates error messages.
     * This method drains the queue and formats all errors into a readable string.
     * 
     * @return string Formatted error messages, one per line
     */
    private function collectOpenSSLErrors(): string
    {
        $errors = '';

        while ($msg = openssl_error_string()) {
            $errors .= $msg . PHP_EOL;
        }

        return $errors ?: 'No detailed OpenSSL errors available.';
    }

    /**
     * Get OpenSSL configuration and environment information
     * 
     * Useful for debugging OpenSSL configuration issues. Returns information about:
     * - OpenSSL version
     * - Configuration file path (detected or specified)
     * - Configuration file existence
     * - Environment variable settings
     * 
     * @return array Associative array with configuration details
     */
    public function checkConfig(): array
    {
        $info = [
            'openssl_version' => OPENSSL_VERSION_TEXT,
            'config_path' => $this->opensslConfigPath,
            'config_exists' => $this->opensslConfigPath ? file_exists($this->opensslConfigPath) : false,
            'openssl_conf_env' => getenv('OPENSSL_CONF'),
        ];

        return $info;
    }
}
