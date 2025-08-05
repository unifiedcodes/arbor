<?php

namespace Arbor\flash;

use InvalidArgumentException;
use Arbor\Contracts\session\SessionInterface;

class Message
{
    private array $messageTypes = [
        'info' => 'alert-info'
    ];

    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->session->start();
    }

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

    public function get(string $type, bool $remove = true): array
    {
        return $this->session->getFlash($type, $remove);
    }

    public function all(bool $remove = true): array
    {
        return $this->session->getFlash(null, $remove);
    }

    public function has(?string $type = null): bool
    {
        return $this->session->hasFlash($type);
    }

    public function clear(?string $type = null): void
    {
        $this->session->getFlash($type, true);
    }

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

    public function registerType(string $type, string $cssClass): void
    {
        $this->messageTypes[$type] = $cssClass;
    }

    public function registerTypes(array $types): void
    {
        $this->messageTypes = array_merge($this->messageTypes, $types);
    }

    public function getTypes(): array
    {
        return $this->messageTypes;
    }

    public function getCssClass(string $type): string
    {
        return $this->messageTypes[$type] ?? 'alert-info';
    }

    public function isValidType(string $type): bool
    {
        return isset($this->messageTypes[$type]);
    }

    public function toArray(?string $type = null, bool $remove = true): array
    {
        return $type ? [$type => $this->get($type, $remove)] : $this->all($remove);
    }

    public function toJson(?string $type = null, bool $remove = true): string
    {
        $messages = $type ? [$type => $this->get($type, $remove)] : $this->all($remove);
        return json_encode($messages, JSON_THROW_ON_ERROR);
    }
}
