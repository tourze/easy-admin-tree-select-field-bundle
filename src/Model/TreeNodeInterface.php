<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Model;

/**
 * 树节点接口
 */
interface TreeNodeInterface
{
    /**
     * 获取节点ID
     */
    public function getId(): mixed;

    /**
     * 获取节点显示标签
     */
    public function getLabel(): string;

    /**
     * 获取父节点ID
     */
    public function getParentId(): mixed;

    /**
     * 获取子节点列表
     * @return TreeNodeInterface[]
     */
    public function getChildren(): array;

    /**
     * 设置子节点列表
     * @param TreeNodeInterface[] $children
     */
    public function setChildren(array $children): void;

    /**
     * 获取节点层级深度
     */
    public function getLevel(): int;

    /**
     * 设置节点层级深度
     */
    public function setLevel(int $level): void;

    /**
     * 获取节点元数据
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * 设置节点元数据
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void;

    /**
     * 是否为叶子节点
     */
    public function isLeaf(): bool;

    /**
     * 是否可选中
     */
    public function isSelectable(): bool;

    /**
     * 是否默认展开
     */
    public function isExpanded(): bool;

    /**
     * 设置是否展开
     */
    public function setExpanded(bool $expanded): void;
}
