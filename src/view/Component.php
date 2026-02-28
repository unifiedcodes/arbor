<?php

namespace Arbor\view;


use Arbor\support\path\Uri;
use RuntimeException;


final class Component
{
    private array $slots = [];
    private array $captureStack = [];

    public function __construct(
        private Uri $uri,
        private array $data = []
    ) {}

    public function uri(): Uri
    {
        return $this->uri;
    }

    public function data(): array
    {
        return $this->data;
    }


    public function slot(string $name = 'default'): string
    {
        return isset($this->slots[$name])
            ? implode('', $this->slots[$name])
            : '';
    }


    public function slots(): array
    {
        return $this->slots;
    }


    public function startSlot(string $name): void
    {
        $this->captureStack[] = ['type' => 'slot', 'name' => $name];
        ob_start();
    }


    public function endSlot(): void
    {
        if (empty($this->captureStack)) {
            throw new RuntimeException('No active capture to end.');
        }

        $context = array_pop($this->captureStack);

        if ($context['type'] !== 'slot') {
            throw new RuntimeException('Mismatched slot ending.');
        }

        $content = ob_get_clean();

        // overwrite previous stack
        $this->slots[$context['name']] = [$content];
    }


    public function startPush(string $name): void
    {
        $this->captureStack[] = ['type' => 'push', 'name' => $name];
        ob_start();
    }


    public function endPush(): void
    {
        if (empty($this->captureStack)) {
            throw new RuntimeException('No active capture to end.');
        }

        $context = array_pop($this->captureStack);

        if ($context['type'] !== 'push') {
            throw new RuntimeException('Mismatched push ending.');
        }

        $content = ob_get_clean();

        // auto-create slot if missing
        $this->slots[$context['name']] ??= [];

        $this->slots[$context['name']][] = $content;
    }


    public function startDefault(): void
    {
        $this->startSlot('default');
    }


    public function endDefault(): void
    {
        $this->endSlot();
    }
}
