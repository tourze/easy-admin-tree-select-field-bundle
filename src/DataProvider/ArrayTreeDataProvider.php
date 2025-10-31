<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DataProvider;

use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNode;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNodeInterface;

/**
 * 数组数据提供者
 */
class ArrayTreeDataProvider extends AbstractTreeDataProvider
{
    /** @var array<string, mixed>[] */
    private array $data = [];

    /**
     * @param array<string, mixed>[] $data
     * @param array<string, mixed> $options
     */
    public function __construct(array $data, array $options = [])
    {
        parent::__construct($options);
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $options
     * @return TreeNodeInterface[]
     */
    public function getTreeData(array $options = []): array
    {
        $nodes = $this->convertArrayToNodes($this->data);

        return $this->buildTree($nodes);
    }

    /**
     * 将数组数据转换为树节点对象
     */
    /**
     * @param array<string, mixed>[] $data
     * @return TreeNodeInterface[]
     */
    protected function convertArrayToNodes(array $data): array
    {
        $nodes = [];

        foreach ($data as $item) {
            $node = $this->createNodeFromItem($item);
            $this->configureNodeProperties($node, $item);
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * 从数组项创建基础节点
     *
     * @param array<string, mixed> $item
     */
    private function createNodeFromItem(array $item): TreeNode
    {
        $label = $item['label'] ?? $item['name'] ?? '';
        $metadata = $item['metadata'] ?? [];

        // 确保label是字符串类型
        $labelString = is_string($label) ? $label : (is_scalar($label) ? (string) $label : '');

        return new TreeNode(
            $item['id'] ?? null,
            $labelString,
            $item['parent_id'] ?? $item['parentId'] ?? null,
            is_array($metadata) ? $metadata : []
        );
    }

    /**
     * 配置节点的可选属性
     *
     * @param array<string, mixed> $item
     */
    private function configureNodeProperties(TreeNode $node, array $item): void
    {
        if (isset($item['selectable']) && is_bool($item['selectable'])) {
            $node->setSelectable($item['selectable']);
        }

        if (isset($item['expanded']) && is_bool($item['expanded'])) {
            $node->setExpanded($item['expanded']);
            // 标记该节点的展开状态由数据显式指定，避免被默认 expanded_level 覆盖
            $meta = $node->getMetadata();
            $meta['_explicit_expanded'] = true;
            $node->setMetadata($meta);
        }
    }

    /**
     * @return TreeNodeInterface[]
     */
    public function getRootNodes(): array
    {
        $nodes = $this->convertArrayToNodes($this->data);
        $roots = [];

        foreach ($nodes as $node) {
            if (null === $node->getParentId()) {
                $roots[] = $node;
            }
        }

        return $roots;
    }

    /**
     * @return TreeNodeInterface[]
     */
    public function getChildrenNodes(mixed $parentId): array
    {
        $nodes = $this->convertArrayToNodes($this->data);
        $children = [];

        foreach ($nodes as $node) {
            if ($node->getParentId() === $parentId) {
                $children[] = $node;
            }
        }

        return $children;
    }
}
