<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\DataProvider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\ArrayTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNode;

/**
 * @internal
 */
#[CoversClass(ArrayTreeDataProvider::class)]
final class ArrayTreeDataProviderTest extends TestCase
{
    /**
     * @return array<string, mixed>[]
     */
    private function getTestData(): array
    {
        return [
            ['id' => 1, 'label' => 'Root 1', 'parent_id' => null],
            ['id' => 2, 'label' => 'Root 2', 'parent_id' => null],
            ['id' => 3, 'label' => 'Child 1.1', 'parent_id' => 1],
            ['id' => 4, 'label' => 'Child 1.2', 'parent_id' => 1],
            ['id' => 5, 'label' => 'Child 2.1', 'parent_id' => 2],
            ['id' => 6, 'label' => 'Grandchild 1.1.1', 'parent_id' => 3],
            ['id' => 7, 'label' => 'Grandchild 1.1.2', 'parent_id' => 3],
        ];
    }

    public function testConstructorInitializesWithData(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $this->assertInstanceOf(ArrayTreeDataProvider::class, $provider);
    }

    public function testGetTreeDataBuildsCorrectTreeStructure(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $tree = $provider->getTreeData();

        // 验证根节点数量
        $this->assertCount(2, $tree);

        // 验证第一个根节点
        $root1 = $tree[0];
        $this->assertEquals(1, $root1->getId());
        $this->assertEquals('Root 1', $root1->getLabel());
        $this->assertNull($root1->getParentId());
        $this->assertCount(2, $root1->getChildren());

        // 验证子节点
        $child1 = $root1->getChildren()[0];
        $this->assertEquals(3, $child1->getId());
        $this->assertEquals('Child 1.1', $child1->getLabel());
        $this->assertEquals(1, $child1->getParentId());
        $this->assertCount(2, $child1->getChildren());

        // 验证孙节点
        $grandchild1 = $child1->getChildren()[0];
        $this->assertEquals(6, $grandchild1->getId());
        $this->assertEquals('Grandchild 1.1.1', $grandchild1->getLabel());
        $this->assertEquals(3, $grandchild1->getParentId());
        $this->assertTrue($grandchild1->isLeaf());
    }

    public function testGetTreeDataWithLevelsSetsCorrectLevels(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $tree = $provider->getTreeData();

        // 验证根节点级别
        $this->assertEquals(0, $tree[0]->getLevel());
        $this->assertEquals(0, $tree[1]->getLevel());

        // 验证子节点级别
        $this->assertEquals(1, $tree[0]->getChildren()[0]->getLevel());
        $this->assertEquals(1, $tree[0]->getChildren()[1]->getLevel());

        // 验证孙节点级别
        $this->assertEquals(2, $tree[0]->getChildren()[0]->getChildren()[0]->getLevel());
        $this->assertEquals(2, $tree[0]->getChildren()[0]->getChildren()[1]->getLevel());
    }

    public function testGetTreeDataWithExpandedLevelOption(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data, ['expanded_level' => 2]);

        $tree = $provider->getTreeData();

        // 验证根节点是展开的
        $this->assertTrue($tree[0]->isExpanded());
        $this->assertTrue($tree[1]->isExpanded());

        // 验证第一级子节点是展开的
        $this->assertTrue($tree[0]->getChildren()[0]->isExpanded());
        $this->assertTrue($tree[0]->getChildren()[1]->isExpanded());

        // 验证第二级子节点不再默认展开
        $this->assertFalse($tree[0]->getChildren()[0]->getChildren()[0]->isExpanded());
    }

    public function testConvertArrayToNodesWithDifferentFieldNames(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Test Node', 'parentId' => null],
            ['id' => 2, 'label' => 'Label Node', 'parent_id' => 1],
        ];

        $provider = new ArrayTreeDataProvider($data);
        $tree = $provider->getTreeData();

        $this->assertEquals('Test Node', $tree[0]->getLabel());
        $this->assertEquals('Label Node', $tree[0]->getChildren()[0]->getLabel());
    }

    public function testConvertArrayToNodesWithSelectableAndExpanded(): void
    {
        $data = [
            [
                'id' => 1,
                'label' => 'Selectable Node',
                'parent_id' => null,
                'selectable' => true,
                'expanded' => true,
            ],
            [
                'id' => 2,
                'label' => 'Non-selectable Node',
                'parent_id' => null,
                'selectable' => false,
                'expanded' => false,
            ],
        ];

        $provider = new ArrayTreeDataProvider($data);
        $tree = $provider->getTreeData();

        $this->assertTrue($tree[0]->isSelectable());
        $this->assertTrue($tree[0]->isExpanded());
        $this->assertFalse($tree[1]->isSelectable());
        $this->assertFalse($tree[1]->isExpanded());
    }

    public function testConvertArrayToNodesWithMetadata(): void
    {
        $data = [
            [
                'id' => 1,
                'label' => 'Node with metadata',
                'parent_id' => null,
                'metadata' => ['custom' => 'value', 'type' => 'special'],
            ],
        ];

        $provider = new ArrayTreeDataProvider($data);
        $tree = $provider->getTreeData();

        $metadata = $tree[0]->getMetadata();
        $this->assertEquals('value', $metadata['custom']);
        $this->assertEquals('special', $metadata['type']);
    }

    public function testFindNodeByIdFindsCorrectNode(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $node = $provider->findNodeById(3);

        $this->assertNotNull($node);
        $this->assertEquals(3, $node->getId());
        $this->assertEquals('Child 1.1', $node->getLabel());
        $this->assertEquals(1, $node->getParentId());
    }

    public function testFindNodeByIdReturnsNullForNonExistentId(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $node = $provider->findNodeById(999);

        $this->assertNull($node);
    }

    public function testFindNodesByIdsReturnsCorrectNodes(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $nodes = $provider->findNodesByIds([1, 3, 6]);

        $this->assertCount(3, $nodes);
        $this->assertEquals(1, $nodes[0]->getId());
        $this->assertEquals(3, $nodes[1]->getId());
        $this->assertEquals(6, $nodes[2]->getId());
    }

    public function testFindNodesByIdsIgnoresNonExistentIds(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $nodes = $provider->findNodesByIds([1, 999, 3, 888]);

        $this->assertCount(2, $nodes);
        $this->assertEquals(1, $nodes[0]->getId());
        $this->assertEquals(3, $nodes[1]->getId());
    }

    public function testSearchNodesFindsMatchingNodes(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $results = $provider->searchNodes('Child');

        $this->assertCount(3, $results);
        // 验证所有结果都包含 "Child"
        foreach ($results as $node) {
            $this->assertStringContainsString('Child', $node->getLabel());
        }
    }

    public function testSearchNodesIsCaseInsensitive(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $results = $provider->searchNodes('child');

        $this->assertCount(3, $results);
    }

    public function testSearchNodesReturnsEmptyForNoMatches(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $results = $provider->searchNodes('NonExistent');

        $this->assertEmpty($results);
    }

    public function testGetRootNodesReturnsOnlyRootNodes(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $roots = $provider->getRootNodes();

        $this->assertCount(2, $roots);
        $this->assertEquals(1, $roots[0]->getId());
        $this->assertEquals(2, $roots[1]->getId());
        $this->assertNull($roots[0]->getParentId());
        $this->assertNull($roots[1]->getParentId());
    }

    public function testGetChildrenNodesReturnsCorrectChildren(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $children = $provider->getChildrenNodes(1);

        $this->assertCount(2, $children);
        $this->assertEquals(3, $children[0]->getId());
        $this->assertEquals(4, $children[1]->getId());
        $this->assertEquals(1, $children[0]->getParentId());
        $this->assertEquals(1, $children[1]->getParentId());
    }

    public function testGetChildrenNodesReturnsEmptyForLeafNodes(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $children = $provider->getChildrenNodes(6);

        $this->assertEmpty($children);
    }

    public function testGetChildrenNodesReturnsEmptyForNonExistentParent(): void
    {
        $data = $this->getTestData();
        $provider = new ArrayTreeDataProvider($data);

        $children = $provider->getChildrenNodes(999);

        $this->assertEmpty($children);
    }

    public function testBuildTreeWithEmptyArray(): void
    {
        $provider = new ArrayTreeDataProvider([]);

        $tree = $provider->getTreeData();

        $this->assertEmpty($tree);
    }

    public function testBuildTreeWithSingleNode(): void
    {
        $data = [
            ['id' => 1, 'label' => 'Single Node', 'parent_id' => null],
        ];

        $provider = new ArrayTreeDataProvider($data);
        $tree = $provider->getTreeData();

        $this->assertCount(1, $tree);
        $this->assertEquals(1, $tree[0]->getId());
        $this->assertEquals('Single Node', $tree[0]->getLabel());
        $this->assertTrue($tree[0]->isLeaf());
    }

    public function testBuildTreeHandlesOrphanNodes(): void
    {
        $data = [
            ['id' => 1, 'label' => 'Root', 'parent_id' => null],
            ['id' => 2, 'label' => 'Orphan', 'parent_id' => 999], // 父节点不存在
        ];

        $provider = new ArrayTreeDataProvider($data);
        $tree = $provider->getTreeData();

        // 只有根节点会被包含在树中
        $this->assertCount(1, $tree);
        $this->assertEquals(1, $tree[0]->getId());
    }

    public function testConstructorAcceptsOptions(): void
    {
        $data = $this->getTestData();
        $options = [
            'max_depth' => 3,
            'expanded_level' => 2,
            'sortable' => false,
        ];

        $provider = new ArrayTreeDataProvider($data, $options);

        $this->assertInstanceOf(ArrayTreeDataProvider::class, $provider);
    }

    protected function setUp(): void
    {
        // 基本设置
    }
}
