<?php

namespace Arbor\flash;

use Arbor\flash\Message;

class View
{
    private Message $flashMessage;
    private array $templates = [];
    private string $defaultTemplate = '<div class="{class}" role="alert">{message}</div>';

    public function __construct(Message $flashMessage)
    {
        $this->flashMessage = $flashMessage;
    }

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

    private function renderMessage(mixed $messageData, string $type, string $template): string
    {
        $message = is_string($messageData) ? $messageData : ($messageData['message'] ?? '');
        $data = is_array($messageData) ? ($messageData['data'] ?? []) : [];
        $cssClass = $this->flashMessage->getCssClass($type);

        $replacements = [
            '{message}' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            '{type}' => $type,
            '{class}' => $cssClass,
            '{timestamp}' => $messageData['timestamp'] ?? time(),
        ];

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $replacements['{' . $key . '}'] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    // Template Management

    public function setTemplate(string $type, string $template): void
    {
        $this->templates[$type] = $template;
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = array_merge($this->templates, $templates);
    }

    public function getTemplate(string $type): ?string
    {
        return $this->templates[$type] ?? null;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function setDefaultTemplate(string $template): void
    {
        $this->defaultTemplate = $template;
    }

    public function getDefaultTemplate(): string
    {
        return $this->defaultTemplate;
    }
}
