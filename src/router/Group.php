<?php

namespace Arbor\router;

/**
 * Class Group
 *
 * Manages route groups by maintaining a stack of group options.
 * Provides methods to push and pop groups, retrieve the current group ID,
 * compose grouped paths, and inherit middlewares.
 *
 * @package Arbor\router
 */
class Group
{
    /**
     * Stack of active group options.
     *
     * @var array
     */
    private array $groupStack = [];

    /**
     * Collection of all defined groups, indexed by group ID.
     *
     * @var array
     */
    protected array $groups = [];

    /**
     * Push a new group onto the stack.
     *
     * Prepares the group options, generates a unique group ID,
     * and stores the group options both in the stack and in the groups collection.
     *
     * @param array $options The group options, such as prefix, namespace, and middlewares.
     *
     * @return void
     */
    public function push(array $options): void
    {
        // Prepare options and generate a unique group ID.
        $groupId = uniqid('group_', true);

        $options = [
            'group_id'    => $groupId,
            'prefix'      => $options['prefix'] ?? '',
            'namespace'   => $options['namespace'] ?? '',
            'middlewares' => $options['middlewares'] ?? [],
        ];

        // Push the entire options array (including group_id) onto the stack.
        $this->groupStack[] = $options;
        // Also store it in the public groups array.
        $this->groups[$groupId] = $options;
    }

    /**
     * Pop the most recent group from the stack.
     *
     * @return void
     */
    public function pop(): void
    {
        array_pop($this->groupStack);
    }

    /**
     * Check if there is an active group.
     *
     * @return bool True if the group stack is not empty, otherwise false.
     */
    public function isGroupActive(): bool
    {
        return !empty($this->groupStack);
    }

    /**
     * Retrieve the current active group ID.
     *
     * @return string|null The group ID of the current active group, or null if no group is active.
     */
    public function getCurrentGroupId(): ?string
    {
        if (!$this->isGroupActive()) {
            return null;
        }
        // Return the 'group_id' from the last group on the stack.
        $current = end($this->groupStack);
        return $current['group_id'];
    }

    /**
     * Generate a grouped path by combining group prefixes with the given path.
     *
     * @param string $path The original route path.
     *
     * @return string The combined path with group prefixes applied.
     */
    public function getGroupedPath(string $path): string
    {
        $finalPrefix = '';

        foreach ($this->groupStack as $group) {
            $finalPrefix = rtrim($finalPrefix . '/' . trim($group['prefix'], '/'), '/');
        }

        return rtrim($finalPrefix . '/' . ltrim($path, '/'), '/');
    }

    /**
     * Retrieve all inherited middlewares from the active group stack.
     *
     * @return array An array of middlewares inherited from all active groups.
     */
    public function inheritedMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->groupStack as $group) {
            $middlewares = array_merge($middlewares, $group['middlewares']);
        }
        return $middlewares;
    }

    /**
     * Retrieve the middlewares associated with a specific group ID.
     *
     * @param string $groupId The unique identifier of the group.
     *
     * @return array The middlewares associated with the specified group.
     */
    public function getMiddlewares(string $groupId): array
    {
        return $this->groups[$groupId]['middlewares'];
    }


    public function getGroupById($id)
    {
        return $this->groups[$id] ?? null;
    }
}
