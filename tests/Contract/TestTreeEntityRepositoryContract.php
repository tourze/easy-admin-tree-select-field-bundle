<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Contract;

use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;

/**
 * TestTreeEntity 仓储接口
 * 定义树形数据的查询方法契约
 */
interface TestTreeEntityRepositoryContract
{
    /**
     * 查找所有根节点（无父节点的节点）
     *
     * @return array<TestTreeEntity>
     */
    public function findRootNodes(): array;

    /**
     * 查找指定节点的直接子节点
     *
     * @return array<TestTreeEntity>
     */
    public function findDirectChildren(TestTreeEntity $parent): array;

    /**
     * 查找指定节点的所有子孙节点（递归）
     *
     * @return array<TestTreeEntity>
     */
    public function findAllDescendants(TestTreeEntity $parent): array;

    /**
     * 查找指定节点的路径（从根节点到指定节点）
     *
     * @return array<TestTreeEntity>
     */
    public function findPath(TestTreeEntity $node): array;

    /**
     * 查找指定深度的所有节点
     *
     * @return array<TestTreeEntity>
     */
    public function findByDepth(int $depth): array;

    /**
     * 查找活跃的节点
     *
     * @return array<TestTreeEntity>
     */
    public function findActiveNodes(): array;

    /**
     * 查找活跃的根节点
     *
     * @return array<TestTreeEntity>
     */
    public function findActiveRootNodes(): array;

    /**
     * 按名称搜索节点
     *
     * @return array<TestTreeEntity>
     */
    public function findByNameLike(string $name): array;

    /**
     * 获取树形结构的完整数据（层级格式）
     *
     * @return array<mixed>
     */
    public function getTreeStructure(): array;

    /**
     * 获取节点的层级数（深度）
     */
    public function getNodeDepth(TestTreeEntity $node): int;

    /**
     * 检查节点是否为叶子节点
     */
    public function isLeafNode(TestTreeEntity $node): bool;

    /**
     * 获取节点的子节点数量
     */
    public function getChildrenCount(TestTreeEntity $node): int;

    /**
     * 获取树的最大深度
     */
    public function getMaxDepth(): int;

    /**
     * 保存实体
     */
    public function save(TestTreeEntity $entity, bool $flush = false): void;

    /**
     * 删除实体
     */
    public function remove(TestTreeEntity $entity, bool $flush = false): void;
}
