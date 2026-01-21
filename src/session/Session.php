<?php

namespace Arbor\session;

use Arbor\session\SessionInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * Arbor Session Manager
 * 
 * Provides secure session management with CSRF protection,
 * flash messages, and configurable options.
 * 
 * @package Arbor\session
 */
class Session implements SessionInterface
{
    /**
     * @var array Session configuration
     */
    private array $config = [
        'name' => 'ARBOR_SESSION',
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
        'regenerate_interval' => 300, // 5 minutes
    ];

    /**
     * @var string CSRF token key
     */
    private const CSRF_TOKEN_KEY = '_csrf_token';

    /**
     * @var string Flash messages key
     */
    private const FLASH_KEY = '_flash_messages';

    /**
     * @var string Last regeneration time key
     */
    private const REGEN_TIME_KEY = '_last_regeneration';

    /**
     * Session constructor
     *
     * @param array $config Optional configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Initialize and start the session
     *
     * @param array $config Optional configuration overrides
     * @throws RuntimeException If session cannot be started
     */
    public function start(array $config = []): void
    {
        if ($this->isStarted()) {
            return;
        }

        // Merge additional config
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        // Configure session settings
        $this->configureSession();

        // Start the session
        if (!session_start()) {
            throw new RuntimeException('Failed to start session');
        }

        // Initialize session security
        $this->initializeSecurity();

        // Handle automatic regeneration
        $this->handleRegeneration();
    }

    /**
     * Configure PHP session settings
     */
    private function configureSession(): void
    {
        ini_set('session.name', $this->config['name']);
        ini_set('session.gc_maxlifetime', (string)$this->config['lifetime']);
        ini_set('session.cookie_lifetime', (string)$this->config['lifetime']);
        ini_set('session.cookie_path', $this->config['path']);
        ini_set('session.cookie_domain', $this->config['domain']);
        ini_set('session.cookie_secure', $this->config['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $this->config['httponly'] ? '1' : '0');
        ini_set('session.cookie_samesite', $this->config['samesite']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
    }

    /**
     * Initialize session security features
     */
    private function initializeSecurity(): void
    {
        // Generate CSRF token if not exists
        if (!$this->has(self::CSRF_TOKEN_KEY)) {
            $this->set(self::CSRF_TOKEN_KEY, $this->generateToken());
        }

        // Set initial regeneration time
        if (!$this->has(self::REGEN_TIME_KEY)) {
            $this->set(self::REGEN_TIME_KEY, time());
        }
    }

    /**
     * Handle automatic session regeneration
     */
    private function handleRegeneration(): void
    {
        $lastRegen = $this->get(self::REGEN_TIME_KEY, 0);

        if (time() - $lastRegen > $this->config['regenerate_interval']) {
            $this->regenerate();
        }
    }

    /**
     * Check if session is started
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Set a session value
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     *
     * @return array
     */
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
        $this->initializeSecurity();
    }

    /**
     * Regenerate session ID
     *
     * @param bool $deleteOld Whether to delete the old session
     */
    public function regenerate(bool $deleteOld = true): void
    {
        $this->ensureStarted();

        if (session_regenerate_id($deleteOld)) {
            $this->set(self::REGEN_TIME_KEY, time());
            // Regenerate CSRF token as well
            $this->set(self::CSRF_TOKEN_KEY, $this->generateToken());
        }
    }

    /**
     * Destroy the session
     */
    public function destroy(): void
    {
        if (!$this->isStarted()) {
            return;
        }

        // Clear session data
        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session
        session_destroy();
    }

    /**
     * Get CSRF token
     *
     * @return string
     */
    public function getCsrfToken(): string
    {
        $this->ensureStarted();
        return $this->get(self::CSRF_TOKEN_KEY, '');
    }

    /**
     * Verify CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $this->getCsrfToken();
        return $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    /**
     * Add a flash message
     *
     * @param string $type Message type (success, error, warning, info)
     * @param string $message
     */
    public function flash(string $type, string|array $message): void
    {
        $this->ensureStarted();

        $flash = $this->get(self::FLASH_KEY, []);
        $flash[$type][] = $message;
        $this->set(self::FLASH_KEY, $flash);
    }

    /**
     * Get flash messages
     *
     * @param string|null $type Specific type or null for all
     * @param bool $remove Whether to remove messages after retrieval
     * @return array
     */
    public function getFlash(?string $type = null, bool $remove = true): array
    {
        $this->ensureStarted();

        $flash = $this->get(self::FLASH_KEY, []);

        if ($type) {
            $messages = $flash[$type] ?? [];
            if ($remove) {
                unset($flash[$type]);
                $this->set(self::FLASH_KEY, $flash);
            }
            return $messages;
        }

        if ($remove) {
            $this->remove(self::FLASH_KEY);
        }

        return $flash;
    }

    /**
     * Check if there are flash messages
     *
     * @param string|null $type
     * @return bool
     */
    public function hasFlash(?string $type = null): bool
    {
        $flash = $this->getFlash($type, false);

        if ($type) {
            return !empty($flash);
        }

        return !empty(array_filter($flash));
    }

    /**
     * Get session ID
     *
     * @return string
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Set session ID
     *
     * @param string $id
     * @throws InvalidArgumentException If session is already started
     */
    public function setId(string $id): void
    {
        if ($this->isStarted()) {
            throw new InvalidArgumentException('Cannot set session ID after session has started');
        }

        session_id($id);
    }

    /**
     * Get session name
     *
     * @return string
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Generate a secure random token
     *
     * @param int $length
     * @return string
     */
    private function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Ensure session is started
     *
     * @throws RuntimeException If session is not started
     */
    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            throw new RuntimeException('Session not started. Call start() method first.');
        }
    }

    /**
     * Get session configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update session configuration
     * Note: Some settings can only be changed before session starts
     *
     * @param array $config
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        if ($this->isStarted()) {
            // Only update settings that can be changed during runtime
            $runtimeSettings = ['lifetime', 'path', 'domain', 'secure', 'httponly', 'samesite'];

            foreach ($runtimeSettings as $setting) {
                if (isset($config[$setting])) {
                    ini_set("session.cookie_$setting", (string)$config[$setting]);
                }
            }
        }
    }
}
