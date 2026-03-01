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
        $this->captureStack[] = [
            'type'  => 'slot',
            'name'  => $name,
            'level' => ob_get_level(),
        ];

        ob_start();
    }


    public function endSlot(): void
    {
        if (empty($this->captureStack)) {
            throw new RuntimeException('No active capture to end.');
        }

        $context = array_pop($this->captureStack);

        if ($context['type'] !== 'slot') {
            throw new RuntimeException(
                "Mismatched capture ending. "
                    . "Attempted to close slot but current capture is '{$context['type']}' '{$context['name']}'."
            );
        }

        // buffer integrity check
        $current = ob_get_level();
        $expected = $context['level'] + 1;

        if ($current !== $expected) {
            throw new RuntimeException(
                "Output buffer corruption while closing slot '{$context['name']}'. "
                    . "Expected buffer level {$expected}, got {$current}. "
                    . "This may indicate manual output buffer manipulation "
                    . "inside the slot."
            );
        }

        $content = ob_get_clean();

        $this->slots[$context['name']] = [$content];
    }


    public function startPush(string $name): void
    {
        $this->captureStack[] = [
            'type'  => 'push',
            'name'  => $name,
            'level' => ob_get_level(),
        ];

        ob_start();
    }


    public function endPush(): void
    {
        if (empty($this->captureStack)) {
            throw new RuntimeException('No active capture to end.');
        }

        $context = array_pop($this->captureStack);

        if ($context['type'] !== 'push') {
            throw new RuntimeException(
                "You are trying to end a push block, "
                    . "but the last opened block is a {$context['type']} "
                    . "named '{$context['name']}'. "
                    . "Please close blocks in the same order they were opened."
            );
        }

        $current = ob_get_level();
        $expected = $context['level'] + 1;

        if ($current !== $expected) {
            throw new RuntimeException(
                "Output buffer corruption while closing push '{$context['name']}'. "
                    . "Expected buffer level {$expected}, got {$current}. "
                    . "This may indicate manual output buffer manipulation "
                    . "inside the push."
            );
        }

        $content = ob_get_clean();

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


    public function hasOpenCaptures(): bool
    {
        return !empty($this->captureStack);
    }


    public function openCaptures(): array
    {
        return $this->captureStack;
    }
}
