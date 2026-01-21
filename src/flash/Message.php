<?php

namespace Arbor\flash;

use InvalidArgumentException;
use Arbor\session\SessionInterface;

/**
 * Flash Message Manager
 * 
 * This class provides a convenient interface for managing flash messages in a session.
 * Flash messages are temporary messages that persist across a single redirect, commonly
 * used for displaying status messages, alerts, or notifications to users.
 * 
 * Features:
 * - Type-based message categorization with CSS class mapping
 * - Session-based storage with automatic cleanup
 * - Support for additional data payload
 * - Flexible retrieval and persistence options
 * - JSON serialization support
 * 
 * @package Arbor\flash
 */
class Message
{
    /**
     * Default message types and their corresponding CSS classes
     * 
     * @var array<string, string> Array mapping message types to CSS classes
     */
    private array $messageTypes = [
        'info' => 'alert-info'
    ];

    /**
     * Session interface for storing and retrieving flash messages
     * 
     * @var SessionInterface
     */
    protected SessionInterface $session;

    /**
     * Initialize the flash message manager
     * 
     * @param SessionInterface $session The session interface to use for storage
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->session->start();
    }

    /**
     * Add a new flash message to the session
     * 
     * @param string $type The message type (must be registered)
     * @param string $message The message content
     * @param array $data Optional additional data to store with the message
     * @return self Returns this instance for method chaining
     * @throws InvalidArgumentException If the message type is not registered
     */
    public function add(string $type, string $message, array $data = []): self
    {
        if (!$this->isValidType($type)) {
            throw new InvalidArgumentException("Invalid flash message type: {$type}");
        }

        $flashData = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time(),
            'data' => $data,
        ];

        $this->session->flash($type, $flashData);
        return $this;
    }

    /**
     * Retrieve flash messages of a specific type
     * 
     * @param string $type The message type to retrieve
     * @param bool $remove Whether to remove the messages after retrieval (default: true)
     * @return array Array of messages for the specified type
     */
    public function get(string $type, bool $remove = true): array
    {
        return $this->session->getFlash($type, $remove);
    }

    /**
     * Retrieve all flash messages regardless of type
     * 
     * @param bool $remove Whether to remove the messages after retrieval (default: true)
     * @return array Associative array with message types as keys and message arrays as values
     */
    public function all(bool $remove = true): array
    {
        return $this->session->getFlash(null, $remove);
    }

    /**
     * Check if flash messages exist
     * 
     * @param string|null $type Optional message type to check for. If null, checks for any messages
     * @return bool True if messages exist, false otherwise
     */
    public function has(?string $type = null): bool
    {
        return $this->session->hasFlash($type);
    }

    /**
     * Clear flash messages from the session
     * 
     * @param string|null $type Optional message type to clear. If null, clears all messages
     * @return void
     */
    public function clear(?string $type = null): void
    {
        $this->session->getFlash($type, true);
    }

    /**
     * Keep flash messages for another request cycle
     * 
     * This method prevents flash messages from being automatically removed,
     * making them available for the next request as well.
     * 
     * @param string|null $type Optional message type to keep. If null, keeps all messages
     * @return void
     */
    public function keep(?string $type = null): void
    {
        $messages = $this->all(false);

        if ($type && isset($messages[$type])) {
            foreach ($messages[$type] as $message) {
                $this->session->flash($type, $message);
            }
        } elseif (!$type) {
            foreach ($messages as $messageType => $typeMessages) {
                foreach ($typeMessages as $message) {
                    $this->session->flash($messageType, $message);
                }
            }
        }
    }

    /**
     * Register a new message type with its CSS class
     * 
     * @param string $type The message type identifier
     * @param string $cssClass The CSS class to associate with this type
     * @return void
     */
    public function registerType(string $type, string $cssClass): void
    {
        $this->messageTypes[$type] = $cssClass;
    }

    /**
     * Register multiple message types at once
     * 
     * @param array<string, string> $types Associative array of type => CSS class mappings
     * @return void
     */
    public function registerTypes(array $types): void
    {
        $this->messageTypes = array_merge($this->messageTypes, $types);
    }

    /**
     * Get all registered message types and their CSS classes
     * 
     * @return array<string, string> Array mapping message types to CSS classes
     */
    public function getTypes(): array
    {
        return $this->messageTypes;
    }

    /**
     * Get the CSS class for a specific message type
     * 
     * @param string $type The message type
     * @return string The CSS class, or 'alert-info' as default if type not found
     */
    public function getCssClass(string $type): string
    {
        return $this->messageTypes[$type] ?? 'alert-info';
    }

    /**
     * Check if a message type is registered and valid
     * 
     * @param string $type The message type to validate
     * @return bool True if the type is registered, false otherwise
     */
    public function isValidType(string $type): bool
    {
        return isset($this->messageTypes[$type]);
    }

    /**
     * Convert flash messages to array format
     * 
     * @param string|null $type Optional message type to convert. If null, converts all messages
     * @param bool $remove Whether to remove the messages after retrieval (default: true)
     * @return array Array representation of the messages
     */
    public function toArray(?string $type = null, bool $remove = true): array
    {
        return $type ? [$type => $this->get($type, $remove)] : $this->all($remove);
    }

    /**
     * Convert flash messages to JSON format
     * 
     * @param string|null $type Optional message type to convert. If null, converts all messages
     * @param bool $remove Whether to remove the messages after retrieval (default: true)
     * @return string JSON representation of the messages
     * @throws \JsonException If JSON encoding fails
     */
    public function toJson(?string $type = null, bool $remove = true): string
    {
        $messages = $type ? [$type => $this->get($type, $remove)] : $this->all($remove);
        return json_encode($messages, JSON_THROW_ON_ERROR);
    }
}
