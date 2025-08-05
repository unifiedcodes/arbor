<?php

namespace Arbor\flash;

use Arbor\flash\Message;

/**
 * Flash Message View Class
 * 
 * Handles the rendering and display of flash messages with customizable templates.
 * This class provides functionality to render flash messages of different types
 * using either custom templates or a default template structure.
 */
class View
{
    /**
     * Flash message handler instance
     * 
     * @var Message The flash message object that manages message storage and retrieval
     */
    private Message $flashMessage;

    /**
     * Custom templates for different message types
     * 
     * @var array Associative array where keys are message types and values are HTML templates
     */
    private array $templates = [];

    /**
     * Default HTML template for rendering messages
     * 
     * @var string The fallback template used when no custom template is defined for a message type
     */
    private string $defaultTemplate = '<div class="{class}" role="alert">{message}</div>';

    /**
     * Constructor
     * 
     * Initializes the view with a flash message handler
     * 
     * @param Message $flashMessage The flash message instance to use for retrieving messages
     */
    public function __construct(Message $flashMessage)
    {
        $this->flashMessage = $flashMessage;
    }

    /**
     * Render flash messages of a specific type
     * 
     * Retrieves and renders all messages of the specified type using the appropriate template.
     * Messages are HTML-escaped for security and can be automatically removed after rendering.
     * 
     * @param string $type The type of messages to render (e.g., 'success', 'error', 'warning')
     * @param bool $remove Whether to remove messages from storage after rendering (default: true)
     * @return string The rendered HTML output containing all messages of the specified type
     */
    public function render(string $type, bool $remove = true): string
    {
        $messages = $this->flashMessage->get($type, $remove);
        $template = $this->templates[$type] ?? $this->defaultTemplate;
        $output = '';

        foreach ($messages as $messageData) {
            $output .= $this->renderMessage($messageData, $type, $template);
        }

        return $output;
    }

    /**
     * Render a single message using the specified template
     * 
     * Processes message data and replaces template placeholders with actual values.
     * Handles both string messages and structured message arrays with additional data.
     * 
     * @param mixed $messageData The message data - can be a string or array with message and metadata
     * @param string $type The message type for CSS class determination
     * @param string $template The HTML template to use for rendering
     * @return string The rendered HTML for the single message
     */
    private function renderMessage(mixed $messageData, string $type, string $template): string
    {
        // Extract message text from either string or array format
        $message = is_string($messageData) ? $messageData : ($messageData['message'] ?? '');

        // Extract additional data if message is in array format
        $data = is_array($messageData) ? ($messageData['data'] ?? []) : [];

        // Get CSS class for the message type
        $cssClass = $this->flashMessage->getCssClass($type);

        // Define standard template replacements
        $replacements = [
            '{message}' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            '{type}' => $type,
            '{class}' => $cssClass,
            '{timestamp}' => $messageData['timestamp'] ?? time(),
        ];

        // Add custom data fields as template replacements
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $replacements['{' . $key . '}'] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }

        // Replace all placeholders in the template
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    //---- Template Management ----

    /**
     * Set a custom template for a specific message type
     * 
     * @param string $type The message type to associate with the template
     * @param string $template The HTML template string with placeholders
     * @return void
     */
    public function setTemplate(string $type, string $template): void
    {
        $this->templates[$type] = $template;
    }

    /**
     * Set multiple templates at once
     * 
     * Merges the provided templates with existing ones, overwriting duplicates.
     * 
     * @param array $templates Associative array of type => template pairs
     * @return void
     */
    public function setTemplates(array $templates): void
    {
        $this->templates = array_merge($this->templates, $templates);
    }

    /**
     * Get the template for a specific message type
     * 
     * @param string $type The message type to retrieve the template for
     * @return string|null The template string, or null if no custom template is set
     */
    public function getTemplate(string $type): ?string
    {
        return $this->templates[$type] ?? null;
    }

    /**
     * Get all custom templates
     * 
     * @return array All currently defined custom templates as type => template pairs
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Set the default template used for message types without custom templates
     * 
     * @param string $template The HTML template string to use as default
     * @return void
     */
    public function setDefaultTemplate(string $template): void
    {
        $this->defaultTemplate = $template;
    }

    /**
     * Get the current default template
     * 
     * @return string The default template string
     */
    public function getDefaultTemplate(): string
    {
        return $this->defaultTemplate;
    }
}
