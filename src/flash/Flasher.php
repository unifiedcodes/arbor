<?php

namespace Arbor\flash;

use Arbor\attributes\ConfigValue;
use Arbor\flash\Message;
use Arbor\flash\View;

/**
 * Flasher - Flash Message Management System
 * 
 * The Flasher class provides a comprehensive interface for managing flash messages
 * in web applications. It acts as a facade that combines message storage/retrieval
 * functionality with view rendering capabilities, allowing developers to easily
 * display temporary messages to users across HTTP requests.
 * 
 * Key features:
 * - Type-based message categorization with CSS class mapping
 * - Flexible message storage with optional data attachments
 * - Template-based rendering system
 * - Batch operations for multiple messages
 * - JSON serialization support
 * - Automatic cleanup and persistence control
 * 
 * @package Arbor\flash
 */
class Flasher
{
    /**
     * Message handler instance for storing and retrieving flash messages
     * 
     * @var Message
     */
    protected Message $message;

    /**
     * View handler instance for rendering flash messages with templates
     * 
     * @var View
     */
    protected view $view;

    /**
     * Initialize the Flasher with message and view handlers
     * 
     * Sets up the flash message system by injecting dependencies and configuring
     * initial message types from configuration. The constructor uses dependency
     * injection to receive the required components and automatically registers
     * any predefined message types with their associated CSS classes.
     * 
     * @param Message $message The message handler for storage operations
     * @param View $view The view handler for rendering operations
     * @param array $initialTypes Array of message types with CSS classes from config
     *                           Format: ['type' => 'css-class', ...]
     */
    public function __construct(
        Message $message,
        View $view,
        #[ConfigValue('flash_message.types')]
        array $initialTypes = []
    ) {
        $this->message = $message;
        $this->view = $view;

        // configuring message types.
        foreach ($initialTypes as $type => $css) {
            $this->message->registerType($type, $css);
        }
    }

    /**
     * Add a single flash message of specified type
     * 
     * Creates a new flash message with the given type and content. The message
     * will be stored and can be retrieved/displayed on subsequent requests.
     * Optional data array can contain additional context or variables for
     * use in message templates.
     * 
     * @param string $type The message type (e.g., 'success', 'error', 'warning')
     * @param string $message The message content to display
     * @param array $data Optional associative array of additional data
     * @return self Returns $this for method chaining
     * 
     * @example
     * $flasher->add('success', 'User created successfully!')
     *         ->add('info', 'Welcome message', ['username' => 'John']);
     */
    public function add(string $type, string $message, array $data = []): self
    {
        $this->message->add($type, $message, $data);
        return $this;
    }

    /**
     * Add multiple flash messages from an array
     * 
     * Processes an array of messages and adds them to the flash storage.
     * Supports two formats for each message:
     * 1. Simple format: ['type' => 'message string']
     * 2. Extended format: ['type' => ['message string', ['data' => 'value']]]
     * 
     * This method is particularly useful for batch operations or when
     * processing validation errors that return multiple messages.
     * 
     * @param array $messages Associative array of messages to add
     *                       Keys are message types, values are either strings or arrays
     * @return self Returns $this for method chaining
     * 
     * @example
     * $flasher->addMultiple([
     *     'error' => 'Validation failed',
     *     'warning' => ['Check your input', ['field' => 'email']],
     *     'info' => 'Form submitted'
     * ]);
     */
    public function addMultiple(array $messages): self
    {
        foreach ($messages as $type => $messageData) {
            if (is_string($messageData)) {
                $this->message->add($type, $messageData);
            } elseif (is_array($messageData)) {
                $message = $messageData[0] ?? '';
                $data = $messageData[1] ?? [];
                $this->message->add($type, $message, $data);
            }
        }

        return $this;
    }

    /**
     * Count the number of flash messages
     * 
     * Returns the total count of messages, either for a specific type
     * or across all types. Useful for determining if messages exist
     * before attempting to display them or for UI indicators.
     * 
     * @param string|null $type Optional message type to count. If null, counts all messages
     * @return int The number of messages found
     * 
     * @example
     * $totalMessages = $flasher->count();        // All messages
     * $errorCount = $flasher->count('error');    // Only error messages
     */
    public function count(?string $type = null): int
    {
        if ($type) {
            return count($this->message->get($type, false));
        }

        $all = $this->message->all(false);
        return array_sum(array_map('count', $all));
    }

    /**
     * Check if flash messages exist
     * 
     * Determines whether any messages are stored, either for a specific
     * type or across all types. This is more efficient than count() when
     * you only need to know if messages exist without knowing the exact number.
     * 
     * @param string|null $type Optional message type to check. If null, checks all types
     * @return bool True if messages exist, false otherwise
     * 
     * @example
     * if ($flasher->has('error')) {
     *     // Handle error messages
     * }
     * 
     * if ($flasher->has()) {
     *     // Handle any messages
     * }
     */
    public function has(?string $type = null): bool
    {
        return $this->message->has($type);
    }

    /**
     * Retrieve flash messages of a specific type
     * 
     * Gets all messages for the specified type. By default, messages are
     * removed after retrieval (typical flash message behavior), but this
     * can be controlled with the $remove parameter.
     * 
     * @param string $type The message type to retrieve
     * @param bool $remove Whether to remove messages after retrieval (default: true)
     * @return array Array of message data for the specified type
     * 
     * @example
     * $errors = $flasher->get('error');        // Get and remove errors
     * $warnings = $flasher->get('warning', false); // Get warnings, keep them
     */
    public function get(string $type, bool $remove = true): array
    {
        return $this->message->get($type, $remove);
    }

    /**
     * Retrieve all flash messages across all types
     * 
     * Returns a multidimensional array containing all stored messages,
     * organized by type. Messages are removed by default after retrieval
     * unless explicitly specified otherwise.
     * 
     * @param bool $remove Whether to remove messages after retrieval (default: true)
     * @return array Associative array with types as keys and message arrays as values
     * 
     * @example
     * $allMessages = $flasher->all();
     * // Returns: ['error' => [...], 'success' => [...], ...]
     */
    public function all(bool $remove = true): array
    {
        return $this->message->all($remove);
    }

    /**
     * Clear flash messages
     * 
     * Removes stored messages either for a specific type or all types.
     * This is useful for cleanup operations or when you want to programmatically
     * clear messages without displaying them.
     * 
     * @param string|null $type Optional message type to clear. If null, clears all messages
     * @return void
     * 
     * @example
     * $flasher->clear('error');    // Clear only error messages
     * $flasher->clear();           // Clear all messages
     */
    public function clear(?string $type = null): void
    {
        $this->message->clear($type);
    }

    /**
     * Keep flash messages for the next request
     * 
     * Prevents messages from being automatically removed, ensuring they
     * persist for additional requests. This is useful when you need messages
     * to survive multiple page loads or redirects.
     * 
     * @param string|null $type Optional message type to keep. If null, keeps all messages
     * @return void
     * 
     * @example
     * $flasher->keep('important');  // Keep important messages
     * $flasher->keep();             // Keep all messages
     */
    public function keep(?string $type = null): void
    {
        $this->message->keep($type);
    }

    /**
     * Register a new message type with its CSS class
     * 
     * Defines a new message type that can be used throughout the application.
     * Each type is associated with a CSS class that will be applied when
     * the message is rendered, allowing for consistent styling.
     * 
     * @param string $type The name of the message type
     * @param string $cssClass The CSS class to apply to this message type
     * @return void
     * 
     * @example
     * $flasher->registerType('custom', 'alert-custom bg-blue-500');
     */
    public function registerType(string $type, string $cssClass): void
    {
        $this->message->registerType($type, $cssClass);
    }

    /**
     * Register multiple message types at once
     * 
     * Batch registration of message types with their associated CSS classes.
     * More efficient than multiple individual registerType() calls and useful
     * for initialization or configuration scenarios.
     * 
     * @param array $types Associative array of types and CSS classes
     *                    Format: ['type' => 'css-class', ...]
     * @return void
     * 
     * @example
     * $flasher->registerTypes([
     *     'info' => 'alert-info text-blue-600',
     *     'warning' => 'alert-warning text-yellow-600',
     *     'danger' => 'alert-danger text-red-600'
     * ]);
     */
    public function registerTypes(array $types): void
    {
        $this->message->registerTypes($types);
    }

    /**
     * Get all registered message types
     * 
     * Returns an array of all currently registered message types and their
     * associated CSS classes. Useful for debugging, configuration verification,
     * or dynamic UI generation.
     * 
     * @return array Associative array of registered types and CSS classes
     */
    public function getTypes(): array
    {
        return $this->message->getTypes();
    }

    /**
     * Get the CSS class for a specific message type
     * 
     * Retrieves the CSS class associated with a particular message type.
     * This is useful when you need to apply styling manually or for
     * custom rendering scenarios.
     * 
     * @param string $type The message type to look up
     * @return string The CSS class associated with the type
     * 
     * @example
     * $errorClass = $flasher->getCssClass('error');
     * // Returns: 'alert-error text-red-600' (or whatever was registered)
     */
    public function getCssClass(string $type): string
    {
        return $this->message->getCssClass($type);
    }

    /**
     * Check if a message type is valid/registered
     * 
     * Validates whether a given message type has been registered and can
     * be used. This is useful for validation before adding messages or
     * in scenarios where type names come from user input.
     * 
     * @param string $type The message type to validate
     * @return bool True if the type is registered, false otherwise
     * 
     * @example
     * if ($flasher->isValidType('custom')) {
     *     $flasher->add('custom', 'Custom message');
     * }
     */
    public function isValidType(string $type): bool
    {
        return $this->message->isValidType($type);
    }

    /**
     * Convert flash messages to array format
     * 
     * Serializes messages to a plain array structure, useful for API responses,
     * logging, or when you need to pass message data to JavaScript or other
     * systems that expect array data.
     * 
     * @param string|null $type Optional message type to convert. If null, converts all types
     * @param bool $remove Whether to remove messages after conversion (default: true)
     * @return array Array representation of the messages
     * 
     * @example
     * $messageArray = $flasher->toArray('error');
     * $allMessages = $flasher->toArray();
     */
    public function toArray(?string $type = null, bool $remove = true): array
    {
        return $this->message->toArray($type, $remove);
    }

    /**
     * Convert flash messages to JSON format
     * 
     * Serializes messages to JSON string, perfect for AJAX responses,
     * API endpoints, or when you need to pass message data to client-side
     * JavaScript for display or processing.
     * 
     * @param string|null $type Optional message type to convert. If null, converts all types
     * @param bool $remove Whether to remove messages after conversion (default: true)
     * @return string JSON representation of the messages
     * 
     * @example
     * $jsonMessages = $flasher->toJson('success');
     * echo $jsonMessages; // {"success": [{"message": "...", "data": {...}}]}
     */
    public function toJson(?string $type = null, bool $remove = true): string
    {
        return $this->message->toJson($type, $remove);
    }

    /**
     * Render flash messages of a specific type as HTML
     * 
     * Generates HTML output for messages of the specified type using
     * the configured templates. This is the primary method for displaying
     * messages in web pages with proper styling and structure.
     * 
     * @param string $type The message type to render
     * @param bool $remove Whether to remove messages after rendering (default: true)
     * @return string HTML output for the specified message type
     * 
     * @example
     * echo $flasher->render('error');    // Renders all error messages
     * echo $flasher->render('success');  // Renders all success messages
     */
    public function render(string $type, bool $remove = true): string
    {
        return $this->view->render($type, $remove);
    }

    /**
     * Render all flash messages as HTML
     * 
     * Generates HTML output for all stored messages across all types.
     * Messages are rendered in the order they were stored, with each
     * type using its respective template. This is the most common method
     * for displaying all pending flash messages.
     * 
     * @param bool $remove Whether to remove messages after rendering (default: true)
     * @return string Complete HTML output for all flash messages
     * 
     * @example
     * echo $flasher->renderAll(); // Renders all messages with their templates
     */
    public function renderAll(bool $remove = true): string
    {
        $allMessages = $this->message->all(false);

        $output = '';
        foreach (array_keys($allMessages) as $type) {
            $output .= $this->view->render($type, $remove);
        }

        return $output;
    }

    /**
     * Set the template for a specific message type
     * 
     * Configures the HTML template that will be used when rendering
     * messages of the specified type. Templates can contain placeholders
     * for message content, CSS classes, and additional data.
     * 
     * @param string $type The message type to configure
     * @param string $template The HTML template string
     * @return void
     * 
     * @example
     * $flasher->setTemplate('error', '<div class="{cssClass}">{message}</div>');
     */
    public function setTemplate(string $type, string $template): void
    {
        $this->view->setTemplate($type, $template);
    }

    /**
     * Set templates for multiple message types
     * 
     * Batch configuration of templates for multiple message types.
     * More efficient than multiple setTemplate() calls and useful
     * for initialization or theme switching scenarios.
     * 
     * @param array $templates Associative array of types and templates
     *                        Format: ['type' => 'template', ...]
     * @return void
     * 
     * @example
     * $flasher->setTemplates([
     *     'success' => '<div class="alert-success">{message}</div>',
     *     'error' => '<div class="alert-error">{message}</div>'
     * ]);
     */
    public function setTemplates(array $templates): void
    {
        $this->view->setTemplates($templates);
    }

    /**
     * Set the default template for unspecified message types
     * 
     * Configures a fallback template that will be used for any message
     * types that don't have a specific template configured. This ensures
     * consistent rendering even for dynamically added message types.
     * 
     * @param string $template The default HTML template string
     * @return void
     * 
     * @example
     * $flasher->setDefaultTemplate('<div class="alert">{message}</div>');
     */
    public function setDefaultTemplate(string $template): void
    {
        $this->view->setDefaultTemplate($template);
    }

    /**
     * Get the template for a specific message type
     * 
     * Retrieves the HTML template configured for the specified message type.
     * Returns null if no specific template is configured for the type.
     * Useful for debugging or dynamic template management.
     * 
     * @param string $type The message type to look up
     * @return string|null The template string, or null if not found
     */
    public function getTemplate(string $type): ?string
    {
        return $this->view->getTemplate($type);
    }

    /**
     * Get all configured templates
     * 
     * Returns an associative array of all configured templates mapped
     * to their message types. Useful for debugging, configuration export,
     * or template management interfaces.
     * 
     * @return array Associative array of types and templates
     */
    public function getTemplates(): array
    {
        return $this->view->getTemplates();
    }

    /**
     * Get the default template
     * 
     * Returns the default template string that is used for message types
     * without specific templates. This template serves as the fallback
     * for rendering any unspecified message types.
     * 
     * @return string The default template string
     */
    public function getDefaultTemplate(): string
    {
        return $this->view->getDefaultTemplate();
    }

    /**
     * Get the Message handler instance
     * 
     * Provides direct access to the underlying Message object for advanced
     * operations or when you need to interact with the message storage
     * layer directly. Use with caution as it bypasses the Flasher's
     * interface abstractions.
     * 
     * @return Message The Message handler instance
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Get the View handler instance
     * 
     * Provides direct access to the underlying View object for advanced
     * template operations or custom rendering scenarios. Use with caution
     * as it bypasses the Flasher's interface abstractions.
     * 
     * @return View The View handler instance
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * Convert the Flasher instance to string
     * 
     * Magic method that allows the Flasher object to be used directly
     * in string contexts. Automatically renders all flash messages
     * as HTML. If rendering fails for any reason, returns empty string
     * to prevent application crashes.
     * 
     * @return string HTML output of all flash messages, or empty string on error
     * 
     * @example
     * echo $flasher;  // Automatically calls renderAll()
     * $html = (string) $flasher;  // Explicit string conversion
     */
    public function __toString(): string
    {
        try {
            return $this->renderAll();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
