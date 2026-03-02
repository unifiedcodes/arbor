<?php

namespace Arbor\view;

use Arbor\support\path\Uri;
use RuntimeException;

/**
 * Represents a renderable view component.
 *
 * A component:
 * - Has a URI identifying its template
 * - Holds associated data
 * - Manages named slots
 * - Supports push-based content stacking
 * - Uses output buffering for content capture
 *
 * It ensures buffer integrity and enforces
 * proper nesting of slot and push blocks.
 */
final class Component
{
    /**
     * Stored slot content.
     *
     * @var array<string, array<int, string>>
     */
    private array $slots = [];

    /**
     * Active output capture stack.
     *
     * Used to track nested slot/push blocks
     * and validate buffer integrity.
     *
     * @var array<int, array{type:string,name:string,level:int}>
     */
    private array $captureStack = [];

    /**
     * @param Uri $uri Component template URI.
     * @param array $data Data passed to the component.
     */
    public function __construct(
        private Uri $uri,
        private array $data = []
    ) {}

    /**
     * Get component URI.
     */
    public function uri(): Uri
    {
        return $this->uri;
    }

    /**
     * Get component data.
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Retrieve rendered slot content.
     *
     * If multiple pushes exist for the slot,
     * their content is concatenated.
     *
     * @param string $name Slot name.
     * @return string
     */
    public function slot(string $name = 'default'): string
    {
        return isset($this->slots[$name])
            ? implode('', $this->slots[$name])
            : '';
    }

    /**
     * Get all slots.
     *
     * @return array<string, array<int, string>>
     */
    public function slots(): array
    {
        return $this->slots;
    }

    /**
     * Start capturing a named slot.
     *
     * @param string $name
     */
    public function startSlot(string $name): void
    {
        $this->captureStack[] = [
            'type'  => 'slot',
            'name'  => $name,
            'level' => ob_get_level(),
        ];

        ob_start();
    }

    /**
     * End the current slot capture.
     *
     * Validates:
     * - Matching block type
     * - Output buffer integrity
     *
     * @throws RuntimeException
     */
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

    /**
     * Start capturing pushed content for a named slot.
     *
     * Unlike startSlot(), push allows multiple
     * content blocks to accumulate.
     *
     * @param string $name
     */
    public function startPush(string $name): void
    {
        $this->captureStack[] = [
            'type'  => 'push',
            'name'  => $name,
            'level' => ob_get_level(),
        ];

        ob_start();
    }

    /**
     * End a push capture block.
     *
     * Appends content to existing slot entries.
     *
     * @throws RuntimeException
     */
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

    /**
     * Start capturing default slot.
     */
    public function startDefault(): void
    {
        $this->startSlot('default');
    }

    /**
     * End capturing default slot.
     */
    public function endDefault(): void
    {
        $this->endSlot();
    }

    /**
     * Check if any capture blocks remain open.
     */
    public function hasOpenCaptures(): bool
    {
        return !empty($this->captureStack);
    }

    /**
     * Get all currently open capture contexts.
     *
     * Useful for debugging unclosed slots/pushes.
     *
     * @return array<int, array{type:string,name:string,level:int}>
     */
    public function openCaptures(): array
    {
        return $this->captureStack;
    }
}
