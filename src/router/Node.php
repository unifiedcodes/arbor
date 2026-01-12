<?php

namespace Arbor\router;

use Arbor\router\Meta;

/**
 * Class Node
 *
 * Represents a node in the routing tree. Each node can contain information
 * about route segments, parameters, associated meta data (such as route handlers
 * and middlewares), and child nodes.
 *
 * @package Arbor\router
 */
class Node
{
    /**
     * The name of the node (typically a route segment).
     *
     * @var string|null
     */
    protected ?string $name;

    /**
     * The name of the parameter if the node represents a parameterized route segment.
     *
     * @var string|null
     */
    protected ?string $parameterName;

    /**
     * Indicates whether the parameter is optional.
     *
     * @var bool
     */
    protected bool $isOptional = false;

    /**
     * Indicates whether the parameter is greedy.
     *
     * @var bool
     */
    protected bool $isGreedy = false;

    /**
     * The segment name of the child that is a parameter, if any.
     *
     * @var string|null
     */
    protected ?string $hasParameterChild = null;

    /**
     * The group ID associated with this node, if the node belongs to a group.
     *
     * @var string|null
     */
    protected ?string $groupId = null;

    /**
     * The child nodes of the current node, indexed by their segment names.
     *
     * @var array<string, Node>
     */
    protected array $children = [];

    /**
     * Meta data for the node, typically including route handlers and middlewares,
     * indexed by HTTP verb.
     *
     * @var array<string, Meta>
     */
    protected array $meta = [];

    /**
     * Node constructor.
     *
     * @param string|null $name The name of the node (route segment). Defaults to null.
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    /**
     * Retrieves the child nodes of this node.
     *
     * @return array<string, Node> The array of child nodes.
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Retrieves a specific child node by its segment.
     *
     * @param string $segment The segment name of the child.
     *
     * @return Node|null The child node if found, otherwise null.
     */
    public function getChild(string $segment): ?Node
    {
        return $this->children[$segment] ?? null;
    }

    /**
     * Adds a child node to the current node.
     *
     * @param string $segmentName The segment name for the child node.
     * @param Node   $node        The node to add as a child.
     *
     * @return Node The added child node.
     */
    public function addChild(string $segmentName, Node $node): Node
    {
        return $this->children[$segmentName] = $node;
    }

    /**
     * Sets the parameter information for the node.
     *
     * @param string|null $parameterName The name of the parameter.
     * @param bool        $isOptional    Whether the parameter is optional.
     * @param bool        $isGreedy      Whether the parameter is greedy.
     *
     * @return self
     */
    public function setParameter(?string $parameterName = null, bool $isOptional = false, bool $isGreedy = false): self
    {
        $this->parameterName = $parameterName;
        $this->isOptional = $isOptional;
        $this->isGreedy = $isGreedy;
        return $this;
    }

    /**
     * Associates meta data with a specific HTTP verb.
     *
     * @param string $verb    The HTTP verb (e.g., GET, POST).
     * @param array  $options The options used to create the Meta object.
     *
     * @return self
     */
    public function setMeta(string $verb, array $options): self
    {
        $this->meta[$verb] = new Meta($options);
        return $this;
    }

    /**
     * Checks if the node has a parameter child.
     *
     * @return string|null The segment name of the parameter child if it exists, otherwise null.
     */
    public function hasParameterChild(): ?string
    {
        return $this->hasParameterChild;
    }

    /**
     * Sets the parameter child for this node.
     *
     * @param string $segment The segment name that represents a parameter child.
     *
     * @return void
     */
    public function setParameterChild(string $segment): void
    {
        $this->hasParameterChild = $segment;
    }

    /**
     * Indicates if the parameter associated with this node is optional.
     *
     * @return bool True if the parameter is optional, otherwise false.
     */
    public function isParameterOptional(): bool
    {
        return $this->isOptional;
    }

    /**
     * Retrieves the parameter name for this node.
     *
     * @return string|null The parameter name, or null if not set.
     */
    public function getParameterName(): ?string
    {
        return $this->parameterName;
    }

    /**
     * Checks if meta data exists for the given HTTP verb.
     *
     * @param string $verb The HTTP verb to check.
     *
     * @return bool True if meta data exists for the verb, otherwise false.
     */
    public function hasVerb(string $verb): bool
    {
        return isset($this->meta[$verb]);
    }

    /**
     * Retrieves the meta data associated with the given HTTP verb.
     *
     * @param string $verb The HTTP verb for which to get the meta data.
     *
     * @return Meta|null The meta data if available, otherwise null.
     */
    public function getMeta(string $verb): ?Meta
    {
        return $this->meta[$verb] ?? null;
    }

    public function hasAnyMeta(): ?array
    {
        return $this->meta ?? null;
    }

    /**
     * Sets the group ID for the node.
     *
     * @param string|null $groupId The group ID to assign.
     *
     * @return void
     */
    public function setGroupId(?string $groupId = null): void
    {
        $this->groupId = $groupId;
    }

    /**
     * Retrieves the group ID associated with the node.
     *
     * @return string|null The group ID if set, otherwise null.
     */
    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    /**
     * Returns a clone of the node without its children.
     *
     * This is useful for returning a simplified version of the node.
     *
     * @return Node A clone of the current node without the children property.
     */
    public function withoutChildren(): Node
    {
        $newNode = clone $this;
        unset($newNode->children);
        return $newNode;
    }


    public function getFullPath(): string
    {
        return '';
    }

    public function isGreedy(): bool
    {
        return $this->isGreedy;
    }

    public function getName()
    {
        return $this->name;
    }
}
