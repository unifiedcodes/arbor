<?php

namespace Arbor\Contracts\Session;

/**
 * Session Contract
 * 
 * Defines the interface for session management in the Arbor framework.
 */
interface SessionInterface
{
    /**
     * Start the session
     *
     * @param array $config Optional configuration overrides
     * @return void
     */
    public function start(array $config = []): void;

    /**
     * Check if session is started
     *
     * @return bool
     */
    public function isStarted(): bool;

    /**
     * Set a session value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get a session value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a session key exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove a session value
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Get all session data
     *
     * @return array
     */
    public function all(): array;

    /**
     * Clear all session data
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Regenerate session ID
     *
     * @param bool $deleteOld Whether to delete the old session
     * @return void
     */
    public function regenerate(bool $deleteOld = true): void;

    /**
     * Destroy the session
     *
     * @return void
     */
    public function destroy(): void;

    /**
     * Get CSRF token
     *
     * @return string
     */
    public function getCsrfToken(): string;

    /**
     * Verify CSRF token
     *
     * @param string $token
     * @return bool
     */
    public function verifyCsrfToken(string $token): bool;

    /**
     * Add a flash message
     *
     * @param string $type Message type (success, error, warning, info)
     * @param string $message
     * @return void
     */
    public function flash(string $type, string $message): void;

    /**
     * Get flash messages
     *
     * @param string|null $type Specific type or null for all
     * @param bool $remove Whether to remove messages after retrieval
     * @return array
     */
    public function getFlash(?string $type = null, bool $remove = true): array;

    /**
     * Check if there are flash messages
     *
     * @param string|null $type
     * @return bool
     */
    public function hasFlash(?string $type = null): bool;

    /**
     * Get session ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set session ID
     *
     * @param string $id
     * @return void
     */
    public function setId(string $id): void;

    /**
     * Get session name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get session configuration
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Update session configuration
     *
     * @param array $config
     * @return void
     */
    public function updateConfig(array $config): void;
}
