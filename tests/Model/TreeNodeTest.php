<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNode;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNodeInterface;

/**
 * @internal
 */
#[CoversClass(TreeNode::class)]
final class TreeNodeTest extends TestCase
{
    protected function setUp(): void
    {
        // TreeNode 测试不需要特殊的初始化
    }

    public function testConstructorSetsBasicProperties(): void
    {
        $node = new TreeNode(1, 'Root Node', null, ['type' => 'category']);

        $this->assertEquals(1, $node->getId());
        $this->assertEquals('Root Node', $node->getLabel());
        $this->assertNull($node->getParentId());
        $this->assertEquals(['type' => 'category'], $node->getMetadata());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEmpty($node->getChildren());
        $this->assertTrue($node->isSelectable());
        $this->assertFalse($node->isExpanded());
    }

    public function testConstructorWithParentId(): void
    {
        $node = new TreeNode(2, 'Child Node', 1);

        $this->assertEquals(2, $node->getId());
        $this->assertEquals('Child Node', $node->getLabel());
        $this->assertEquals(1, $node->getParentId());
        $this->assertEmpty($node->getMetadata());
    }

    public function testConstructorWithStringId(): void
    {
        $node = new TreeNode('uuid-123', 'String ID Node', 'parent-uuid');

        $this->assertEquals('uuid-123', $node->getId());
        $this->assertEquals('String ID Node', $node->getLabel());
        $this->assertEquals('parent-uuid', $node->getParentId());
    }

    public function testImplementsTreeNodeInterface(): void
    {
        $node = new TreeNode(1, 'Test');

        $this->assertInstanceOf(TreeNodeInterface::class, $node);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $metadata = ['priority' => 10, 'icon' => 'fa-folder'];
        $node = new TreeNode(42, 'Test Node', 5, $metadata);

        $this->assertEquals(42, $node->getId());
        $this->assertEquals('Test Node', $node->getLabel());
        $this->assertEquals(5, $node->getParentId());
        $this->assertEquals($metadata, $node->getMetadata());
    }

    public function testSetAndGetChildren(): void
    {
        $parent = new TreeNode(1, 'Parent');
        $child1 = new TreeNode(2, 'Child 1', 1);
        $child2 = new TreeNode(3, 'Child 2', 1);

        $children = [$child1, $child2];
        $parent->setChildren($children);

        $this->assertEquals($children, $parent->getChildren());
        $this->assertCount(2, $parent->getChildren());
        $this->assertSame($child1, $parent->getChildren()[0]);
        $this->assertSame($child2, $parent->getChildren()[1]);
    }

    public function testSetAndGetLevel(): void
    {
        $node = new TreeNode(1, 'Test');

        $this->assertEquals(0, $node->getLevel());

        $node->setLevel(3);
        $this->assertEquals(3, $node->getLevel());

        $node->setLevel(0);
        $this->assertEquals(0, $node->getLevel());
    }

    public function testSetAndGetMetadata(): void
    {
        $node = new TreeNode(1, 'Test');

        $this->assertEmpty($node->getMetadata());

        $metadata = ['css_class' => 'active', 'order' => 100];
        $node->setMetadata($metadata);

        $this->assertEquals($metadata, $node->getMetadata());

        // Test setting empty metadata
        $node->setMetadata([]);
        $this->assertEmpty($node->getMetadata());
    }

    public function testIsLeafWithoutChildren(): void
    {
        $node = new TreeNode(1, 'Leaf Node');

        $this->assertTrue($node->isLeaf());
    }

    public function testIsLeafWithChildren(): void
    {
        $parent = new TreeNode(1, 'Parent');
        $child = new TreeNode(2, 'Child', 1);

        $parent->setChildren([$child]);

        $this->assertFalse($parent->isLeaf());
    }

    public function testIsLeafWithEmptyChildrenArray(): void
    {
        $node = new TreeNode(1, 'Node');
        $node->setChildren([]);

        $this->assertTrue($node->isLeaf());
    }

    public function testSetAndIsSelectable(): void
    {
        $node = new TreeNode(1, 'Test');

        $this->assertTrue($node->isSelectable());

        $node->setSelectable(false);
        $this->assertFalse($node->isSelectable());

        $node->setSelectable(true);
        $this->assertTrue($node->isSelectable());
    }

    public function testSetAndIsExpanded(): void
    {
        $node = new TreeNode(1, 'Test');

        $this->assertFalse($node->isExpanded());

        $node->setExpanded(true);
        $this->assertTrue($node->isExpanded());

        $node->setExpanded(false);
        $this->assertFalse($node->isExpanded());
    }

    public function testComplexTreeStructure(): void
    {
        // Create a complex tree: Root -> Child1 -> Grandchild1
        //                                -> Child2
        $root = new TreeNode(1, 'Root', null, ['level' => 'root']);
        $root->setLevel(0);
        $root->setExpanded(true);

        $child1 = new TreeNode(2, 'Child 1', 1, ['level' => 'child']);
        $child1->setLevel(1);
        $child1->setExpanded(false);

        $child2 = new TreeNode(3, 'Child 2', 1, ['level' => 'child']);
        $child2->setLevel(1);
        $child2->setSelectable(false);

        $grandchild = new TreeNode(4, 'Grandchild', 2, ['level' => 'grandchild']);
        $grandchild->setLevel(2);

        $child1->setChildren([$grandchild]);
        $root->setChildren([$child1, $child2]);

        // Test root node
        $this->assertEquals(1, $root->getId());
        $this->assertEquals('Root', $root->getLabel());
        $this->assertNull($root->getParentId());
        $this->assertEquals(0, $root->getLevel());
        $this->assertFalse($root->isLeaf());
        $this->assertTrue($root->isSelectable());
        $this->assertTrue($root->isExpanded());
        $this->assertCount(2, $root->getChildren());

        // Test first child
        $this->assertEquals(2, $child1->getId());
        $this->assertEquals('Child 1', $child1->getLabel());
        $this->assertEquals(1, $child1->getParentId());
        $this->assertEquals(1, $child1->getLevel());
        $this->assertFalse($child1->isLeaf());
        $this->assertTrue($child1->isSelectable());
        $this->assertFalse($child1->isExpanded());
        $this->assertCount(1, $child1->getChildren());

        // Test second child
        $this->assertEquals(3, $child2->getId());
        $this->assertEquals('Child 2', $child2->getLabel());
        $this->assertEquals(1, $child2->getParentId());
        $this->assertEquals(1, $child2->getLevel());
        $this->assertTrue($child2->isLeaf());
        $this->assertFalse($child2->isSelectable());
        $this->assertEmpty($child2->getChildren());

        // Test grandchild
        $this->assertEquals(4, $grandchild->getId());
        $this->assertEquals('Grandchild', $grandchild->getLabel());
        $this->assertEquals(2, $grandchild->getParentId());
        $this->assertEquals(2, $grandchild->getLevel());
        $this->assertTrue($grandchild->isLeaf());
        $this->assertTrue($grandchild->isSelectable());
        $this->assertFalse($grandchild->isExpanded());
        $this->assertEmpty($grandchild->getChildren());

        // Test metadata
        $this->assertEquals(['level' => 'root'], $root->getMetadata());
        $this->assertEquals(['level' => 'child'], $child1->getMetadata());
        $this->assertEquals(['level' => 'child'], $child2->getMetadata());
        $this->assertEquals(['level' => 'grandchild'], $grandchild->getMetadata());
    }

    public function testNodeWithMixedTypes(): void
    {
        // Test with mixed ID types
        $intNode = new TreeNode(1, 'Integer ID');
        $stringNode = new TreeNode('str-id', 'String ID', 1);
        $floatNode = new TreeNode(1.5, 'Float ID');

        $this->assertIsInt($intNode->getId());
        $this->assertIsString($stringNode->getId());
        $this->assertIsFloat($floatNode->getId());

        $this->assertEquals(1, $intNode->getId());
        $this->assertEquals('str-id', $stringNode->getId());
        $this->assertEquals(1.5, $floatNode->getId());

        // Test parent relationships with mixed types
        $this->assertEquals(1, $stringNode->getParentId());
    }

    public function testMetadataModification(): void
    {
        $node = new TreeNode(1, 'Test');

        // Start with initial metadata
        $initialMetadata = ['status' => 'active', 'priority' => 1];
        $node->setMetadata($initialMetadata);
        $this->assertEquals($initialMetadata, $node->getMetadata());

        // Update metadata completely
        $newMetadata = ['category' => 'electronics', 'featured' => true];
        $node->setMetadata($newMetadata);
        $this->assertEquals($newMetadata, $node->getMetadata());
        $this->assertArrayNotHasKey('status', $node->getMetadata());
        $this->assertArrayNotHasKey('priority', $node->getMetadata());

        // Clear metadata
        $node->setMetadata([]);
        $this->assertEmpty($node->getMetadata());
    }

    public function testDeepNesting(): void
    {
        // Create a deeply nested structure
        $level0 = new TreeNode(0, 'Level 0');
        $level1 = new TreeNode(1, 'Level 1', 0);
        $level2 = new TreeNode(2, 'Level 2', 1);
        $level3 = new TreeNode(3, 'Level 3', 2);

        $level0->setLevel(0);
        $level1->setLevel(1);
        $level2->setLevel(2);
        $level3->setLevel(3);

        $level2->setChildren([$level3]);
        $level1->setChildren([$level2]);
        $level0->setChildren([$level1]);

        // Verify deep nesting structure
        $this->assertFalse($level0->isLeaf());
        $this->assertCount(1, $level0->getChildren());

        $childOfLevel0 = $level0->getChildren()[0];
        $this->assertSame($level1, $childOfLevel0);
        $this->assertEquals(1, $childOfLevel0->getLevel());
        $this->assertFalse($childOfLevel0->isLeaf());

        $childOfLevel1 = $level1->getChildren()[0];
        $this->assertSame($level2, $childOfLevel1);
        $this->assertEquals(2, $childOfLevel1->getLevel());
        $this->assertFalse($childOfLevel1->isLeaf());

        $childOfLevel2 = $level2->getChildren()[0];
        $this->assertSame($level3, $childOfLevel2);
        $this->assertEquals(3, $childOfLevel2->getLevel());
        $this->assertTrue($childOfLevel2->isLeaf());
    }
}
