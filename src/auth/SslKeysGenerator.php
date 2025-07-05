<?php

namespace Arbor\auth;

use Exception;

class SslKeysGenerator
{
    private int $keySize = 2048;
    private string $privateKeyPath;
    private string $publicKeyPath;
    private ?string $opensslConfigPath = null;

    public function __construct(string $privateKeyPath, string $publicKeyPath, int $keySize = 2048, ?string $opensslConfigPath = null)
    {
        $this->privateKeyPath = $privateKeyPath;
        $this->publicKeyPath = $publicKeyPath;
        $this->keySize = $keySize;
        $this->opensslConfigPath = $opensslConfigPath ?? $this->detectDefaultConfig();
    }

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

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Failed to create directory: {$directory}");
            }
        }
    }

    private function saveKey(string $path, string $key, int $permissions): void
    {
        if (file_put_contents($path, $key) === false) {
            throw new Exception("Failed to save key to {$path}");
        }
        chmod($path, $permissions);
    }

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

    private function collectOpenSSLErrors(): string
    {
        $errors = '';

        while ($msg = openssl_error_string()) {
            $errors .= $msg . PHP_EOL;
        }

        return $errors ?: 'No detailed OpenSSL errors available.';
    }

    // Method to test if OpenSSL is working
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
