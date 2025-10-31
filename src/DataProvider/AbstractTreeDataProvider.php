<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DataProvider;

use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNodeInterface;

/**
 * 抽象树数据提供者
 */
abstract class AbstractTreeDataProvider implements TreeDataProviderInterface
{
    /** @var array<string, mixed> */
    protected array $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultOptions(): array
    {
        return [
            'max_depth' => null,
            'expanded_level' => 1,
            'sortable' => true,
        ];
    }

    public function findNodeById(mixed $id): ?TreeNodeInterface
    {
        $nodes = $this->getTreeData();

        return $this->findNodeInTree($nodes, $id);
    }

    /**
     * @param array<mixed> $ids
     * @return TreeNodeInterface[]
     */
    public function findNodesByIds(array $ids): array
    {
        $result = [];
        $nodes = $this->getTreeData();

        foreach ($ids as $id) {
            $node = $this->findNodeInTree($nodes, $id);
            if (null !== $node) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * @param TreeNodeInterface[] $nodes
     */
    protected function findNodeInTree(array $nodes, mixed $id): ?TreeNodeInterface
    {
        foreach ($nodes as $node) {
            if ($node->getId() === $id) {
                return $node;
            }

            $found = $this->findNodeInTree($node->getChildren(), $id);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $options
     * @return TreeNodeInterface[]
     */
    public function searchNodes(string $query, array $options = []): array
    {
        $nodes = $this->getTreeData($options);

        return $this->searchInTree($nodes, $query);
    }

    /**
     * @param TreeNodeInterface[] $nodes
     * @return TreeNodeInterface[]
     */
    protected function searchInTree(array $nodes, string $query): array
    {
        $result = [];
        $pattern = '/(^|\s)' . preg_quote($query, '/') . '(?=\s|\d|$)/iu';

        foreach ($nodes as $node) {
            if (1 === preg_match($pattern, (string) $node->getLabel())) {
                $result[] = $node;
            }

            $childResults = $this->searchInTree($node->getChildren(), $query);
            $result = array_merge($result, $childResults);
        }

        return $result;
    }

    /**
     * @param TreeNodeInterface[] $nodes
     * @return TreeNodeInterface[]
     */
    public function buildTree(array $nodes): array
    {
        $indexed = $this->indexNodesByValidId($nodes);
        $tree = $this->buildTreeStructure($indexed);
        $this->setNodeLevels($tree, 0);

        return $tree;
    }

    /**
     * @param TreeNodeInterface[] $nodes
     * @return array<string|int, TreeNodeInterface>
     */
    private function indexNodesByValidId(array $nodes): array
    {
        $indexed = [];

        foreach ($nodes as $node) {
            $nodeId = $node->getId();
            if ($this->isValidId($nodeId)) {
                // 确保类型安全：$nodeId已通过isValidId验证，必然是string或int
                assert(is_string($nodeId) || is_int($nodeId));
                $indexed[$nodeId] = $node;
            }
        }

        return $indexed;
    }

    /**
     * @param array<string|int, TreeNodeInterface> $indexed
     * @return TreeNodeInterface[]
     */
    private function buildTreeStructure(array $indexed): array
    {
        $tree = [];

        foreach ($indexed as $node) {
            $parentId = $node->getParentId();

            if (null === $parentId) {
                $tree[] = $node;
            } else {
                $this->attachNodeToParent($node, $parentId, $indexed);
            }
        }

        return $tree;
    }

    /**
     * @param array<string|int, TreeNodeInterface> $indexed
     */
    private function attachNodeToParent(TreeNodeInterface $node, mixed $parentId, array $indexed): void
    {
        if (!$this->isValidId($parentId)) {
            return;
        }

        // 确保类型安全：$parentId已通过isValidId验证，必然是string或int
        assert(is_string($parentId) || is_int($parentId));

        if (!isset($indexed[$parentId])) {
            return;
        }

        $parent = $indexed[$parentId];
        $children = $parent->getChildren();
        $children[] = $node;
        $parent->setChildren($children);
    }

    private function isValidId(mixed $id): bool
    {
        return is_string($id) || is_int($id);
    }

    /**
     * @param TreeNodeInterface[] $nodes
     */
    protected function setNodeLevels(array $nodes, int $level): void
    {
        foreach ($nodes as $node) {
            $node->setLevel($level);

            // 若未显式指定 expanded，按 expanded_level 默认展开
            if (null !== $this->options['expanded_level'] && $level < $this->options['expanded_level']) {
                $meta = $node->getMetadata();
                $explicit = isset($meta['_explicit_expanded']) && true === $meta['_explicit_expanded'];
                if (!$explicit) {
                    $node->setExpanded(true);
                }
            }

            if ([] !== $node->getChildren()) {
                $this->setNodeLevels($node->getChildren(), $level + 1);
            }
        }
    }
}
