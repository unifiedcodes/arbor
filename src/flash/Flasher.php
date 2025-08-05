<?php

namespace Arbor\flash;

use Arbor\attributes\ConfigValue;
use Arbor\flash\Message;
use Arbor\flash\View;


class Flasher
{
    protected Message $message;
    protected view $view;

    public function __construct(
        Message $message,
        View $view,
        #[ConfigValue('misc.flash_message_types')]
        array $initialTypes = []
    ) {
        $this->message = $message;
        $this->view = $view;

        // configuring message types.
        foreach ($initialTypes as $type => $css) {
            $this->message->registerType($type, $css);
        }
    }


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


    public function count(?string $type = null): int
    {
        if ($type) {
            return count($this->message->get($type, false));
        }

        $all = $this->message->all(false);
        return array_sum(array_map('count', $all));
    }


    public function has(?string $type = null): bool
    {
        return $this->message->has($type);
    }

    public function add(string $type, string $message, array $data = []): self
    {
        $this->message->add($type, $message, $data);
        return $this;
    }

    public function get(string $type, bool $remove = true): array
    {
        return $this->message->get($type, $remove);
    }

    public function all(bool $remove = true): array
    {
        return $this->message->all($remove);
    }

    public function clear(?string $type = null): void
    {
        $this->message->clear($type);
    }

    public function keep(?string $type = null): void
    {
        $this->message->keep($type);
    }

    public function registerType(string $type, string $cssClass): void
    {
        $this->message->registerType($type, $cssClass);
    }

    public function registerTypes(array $types): void
    {
        $this->message->registerTypes($types);
    }

    public function getTypes(): array
    {
        return $this->message->getTypes();
    }

    public function getCssClass(string $type): string
    {
        return $this->message->getCssClass($type);
    }

    public function isValidType(string $type): bool
    {
        return $this->message->isValidType($type);
    }

    public function toArray(?string $type = null, bool $remove = true): array
    {
        return $this->message->toArray($type, $remove);
    }

    public function toJson(?string $type = null, bool $remove = true): string
    {
        return $this->message->toJson($type, $remove);
    }

    public function render(string $type, bool $remove = true): string
    {
        return $this->view->render($type, $remove);
    }

    public function renderAll(bool $remove = true): string
    {
        $allMessages = $this->message->all($remove);

        $output = '';
        foreach (array_keys($allMessages) as $type) {
            $output .= $this->view->render($type, false);
        }

        return $output;
    }

    public function setTemplate(string $type, string $template): void
    {
        $this->view->setTemplate($type, $template);
    }

    public function setTemplates(array $templates): void
    {
        $this->view->setTemplates($templates);
    }

    public function setDefaultTemplate(string $template): void
    {
        $this->view->setDefaultTemplate($template);
    }

    public function getTemplate(string $type): ?string
    {
        return $this->view->getTemplate($type);
    }

    public function getTemplates(): array
    {
        return $this->view->getTemplates();
    }

    public function getDefaultTemplate(): string
    {
        return $this->view->getDefaultTemplate();
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function __toString(): string
    {
        try {
            return $this->renderAll();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
