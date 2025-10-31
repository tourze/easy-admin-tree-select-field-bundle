<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DataProvider;

use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNodeInterface;

/**
 * 树数据提供者接口
 */
interface TreeDataProviderInterface
{
    /**
     * 获取树形数据
     * @param array<string, mixed> $options 选项参数
     * @return TreeNodeInterface[] 树节点数组
     */
    public function getTreeData(array $options = []): array;

    /**
     * 根据ID查找节点
     */
    public function findNodeById(mixed $id): ?TreeNodeInterface;

    /**
     * 根据多个ID查找节点
     * @param array<mixed> $ids
     * @return TreeNodeInterface[]
     */
    public function findNodesByIds(array $ids): array;

    /**
     * 搜索节点
     * @param string $query 搜索关键词
     * @param array<string, mixed> $options 选项参数
     * @return TreeNodeInterface[] 匹配的节点数组
     */
    public function searchNodes(string $query, array $options = []): array;

    /**
     * 获取根节点
     * @return TreeNodeInterface[]
     */
    public function getRootNodes(): array;

    /**
     * 获取节点的子节点
     * @return TreeNodeInterface[]
     */
    public function getChildrenNodes(mixed $parentId): array;

    /**
     * 构建完整的树结构
     * @param TreeNodeInterface[] $nodes
     * @return TreeNodeInterface[]
     */
    public function buildTree(array $nodes): array;
}
