<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Model;

/**
 * 默认树节点实现
 */
class TreeNode implements TreeNodeInterface
{
    private mixed $id;

    private string $label;

    private mixed $parentId;

    /** @var TreeNodeInterface[] */
    private array $children = [];

    private int $level = 0;

    /** @var array<string, mixed> */
    private array $metadata = [];

    private bool $selectable = true;

    private bool $expanded = false;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        mixed $id,
        string $label,
        mixed $parentId = null,
        array $metadata = [],
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->parentId = $parentId;
        $this->metadata = $metadata;
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getParentId(): mixed
    {
        return $this->parentId;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function isLeaf(): bool
    {
        return [] === $this->children;
    }

    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    public function setSelectable(bool $selectable): void
    {
        $this->selectable = $selectable;
    }

    public function isExpanded(): bool
    {
        return $this->expanded;
    }

    public function setExpanded(bool $expanded): void
    {
        $this->expanded = $expanded;
    }
}
