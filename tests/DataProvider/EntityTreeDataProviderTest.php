<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Tests\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminTreeSelectFieldBundle\DataProvider\EntityTreeDataProvider;
use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;

/**
 * @internal
 */
#[CoversClass(EntityTreeDataProvider::class)]
final class EntityTreeDataProviderTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;

    /** @var MockObject&EntityRepository<object> */
    private MockObject $repository;

    /** @var MockObject&QueryBuilder */
    private MockObject $queryBuilder;

    /** @var MockObject&Query */
    private MockObject $query;

    private EntityTreeDataProvider $provider;

    /** @var class-string<TestTreeEntity> */
    private string $entityClass = TestTreeEntity::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        // 配置 EntityManager mock
        $this->entityManager
            ->method('getRepository')
            ->with($this->entityClass)
            ->willReturn($this->repository)
        ;

        // 配置 Repository mock
        $this->repository
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder)
        ;

        // 配置 QueryBuilder mock 的基本行为
        $this->queryBuilder
            ->method('andWhere')
            ->willReturn($this->queryBuilder)
        ;
        $this->queryBuilder
            ->method('setParameter')
            ->willReturn($this->queryBuilder)
        ;
        $this->queryBuilder
            ->method('addOrderBy')
            ->willReturn($this->queryBuilder)
        ;
        $this->queryBuilder
            ->method('where')
            ->willReturn($this->queryBuilder)
        ;
        $this->queryBuilder
            ->method('join')
            ->willReturn($this->queryBuilder)
        ;
        $this->queryBuilder
            ->method('getQuery')
            ->willReturn($this->query)
        ;

        $this->provider = new EntityTreeDataProvider(
            $this->entityManager,
            $this->entityClass,
            [
                'id_field' => 'id',
                'label_field' => 'name',
                'parent_field' => 'parent',
            ]
        );
    }

    /**
     * 创建模拟实体数据
     * @return object[]
     */
    private function createMockEntities(): array
    {
        // 创建模拟的实体对象
        $root1 = (object) [
            'id' => 1,
            'name' => 'Root Category 1',
            'description' => 'First root category',
            'active' => true,
            'sortOrder' => 1,
            'parent' => null,
        ];

        $root2 = (object) [
            'id' => 2,
            'name' => 'Root Category 2',
            'description' => 'Second root category',
            'active' => true,
            'sortOrder' => 2,
            'parent' => null,
        ];

        $child1_1 = (object) [
            'id' => 3,
            'name' => 'Child 1.1',
            'description' => 'First child of root 1',
            'active' => true,
            'sortOrder' => 1,
            'parent' => $root1,
        ];

        $child1_2 = (object) [
            'id' => 4,
            'name' => 'Child 1.2',
            'description' => 'Second child of root 1',
            'active' => true,
            'sortOrder' => 2,
            'parent' => $root1,
        ];

        $child2_1 = (object) [
            'id' => 5,
            'name' => 'Child 2.1',
            'description' => 'First child of root 2',
            'active' => false,
            'sortOrder' => 1,
            'parent' => $root2,
        ];

        $grandchild1_1_1 = (object) [
            'id' => 6,
            'name' => 'Grandchild 1.1.1',
            'description' => 'First grandchild',
            'active' => true,
            'sortOrder' => 1,
            'parent' => $child1_1,
        ];

        $grandchild1_1_2 = (object) [
            'id' => 7,
            'name' => 'Grandchild 1.1.2',
            'description' => 'Second grandchild',
            'active' => true,
            'sortOrder' => 2,
            'parent' => $child1_1,
        ];

        return [
            'root1' => $root1,
            'root2' => $root2,
            'child1_1' => $child1_1,
            'child1_2' => $child1_2,
            'child2_1' => $child2_1,
            'grandchild1_1_1' => $grandchild1_1_1,
            'grandchild1_1_2' => $grandchild1_1_2,
        ];
    }

    public function testConstructorInitializesCorrectly(): void
    {
        $provider = new EntityTreeDataProvider(
            $this->entityManager,
            $this->entityClass
        );

        $this->assertInstanceOf(EntityTreeDataProvider::class, $provider);
    }

    public function testConstructorWithCustomOptions(): void
    {
        $options = [
            'id_field' => 'customId',
            'label_field' => 'customLabel',
            'parent_field' => 'customParent',
        ];

        $provider = new EntityTreeDataProvider(
            $this->entityManager,
            $this->entityClass,
            $options
        );

        $this->assertInstanceOf(EntityTreeDataProvider::class, $provider);
    }

    public function testGetTreeDataBuildsCorrectTreeStructure(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $tree = $this->provider->getTreeData();

        // 验证根节点数量
        $this->assertCount(2, $tree);

        // 验证第一个根节点
        $root1 = $tree[0];
        $this->assertEquals('Root Category 1', $root1->getLabel());
        $this->assertNull($root1->getParentId());
        $this->assertCount(2, $root1->getChildren());

        // 验证实体元数据存在
        $metadata = $root1->getMetadata();
        $this->assertArrayHasKey('entity', $metadata);
        $this->assertEquals($entities['root1'], $metadata['entity']);

        // 验证子节点
        $child1 = $root1->getChildren()[0];
        $this->assertEquals('Child 1.1', $child1->getLabel());
        $this->assertNotNull($child1->getParentId());
        $this->assertCount(2, $child1->getChildren());

        // 验证孙节点
        $grandchild1 = $child1->getChildren()[0];
        $this->assertEquals('Grandchild 1.1.1', $grandchild1->getLabel());
        $this->assertTrue($grandchild1->isLeaf());
    }

    public function testGetTreeDataWithFilterOptions(): void
    {
        $entities = $this->createMockEntities();
        $activeEntities = array_filter($entities, fn ($entity) => true === (property_exists($entity, 'active') && $entity->active));

        $options = [
            'where' => ['active' => true],
        ];

        // 验证过滤条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with('e.active = :active')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('active', true)
        ;

        // 配置 query mock 返回激活的实体
        $this->query
            ->method('getResult')
            ->willReturn(array_values($activeEntities))
        ;

        $tree = $this->provider->getTreeData($options);

        // 验证返回结果
        $this->assertCount(2, $tree); // 两个根节点都是激活的
    }

    public function testGetTreeDataWithOrderByOptions(): void
    {
        $entities = $this->createMockEntities();
        // 按 sortOrder DESC 排序的实体
        $sortedEntities = [$entities['root2'], $entities['root1']];

        $options = [
            'order_by' => ['sortOrder' => 'DESC', 'name' => 'ASC'],
        ];

        // 验证排序条件被正确应用
        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('addOrderBy')
            ->willReturnCallback(function ($field, $order) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals('e.sortOrder', $field);
                    $this->assertEquals('DESC', $order);
                } elseif (2 === $callCount) {
                    $this->assertEquals('e.name', $field);
                    $this->assertEquals('ASC', $order);
                }

                return $this->queryBuilder;
            })
        ;

        // 配置 query mock 返回排序后的实体
        $this->query
            ->method('getResult')
            ->willReturn($sortedEntities)
        ;

        $tree = $this->provider->getTreeData($options);

        $this->assertCount(2, $tree);

        // 由于按 sortOrder DESC 排序，Root Category 2 应该在前
        $this->assertEquals('Root Category 2', $tree[0]->getLabel());
        $this->assertEquals('Root Category 1', $tree[1]->getLabel());
    }

    public function testGetRootNodesReturnsOnlyRootEntities(): void
    {
        $entities = $this->createMockEntities();
        $rootEntities = [$entities['root1'], $entities['root2']];

        // 验证 WHERE 条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('e.parent IS NULL')
        ;

        // 配置 query mock 返回根节点实体
        $this->query
            ->method('getResult')
            ->willReturn($rootEntities)
        ;

        $roots = $this->provider->getRootNodes();

        $this->assertCount(2, $roots);

        foreach ($roots as $root) {
            $this->assertNull($root->getParentId());
            $this->assertArrayHasKey('entity', $root->getMetadata());
        }
    }

    public function testGetChildrenNodesReturnsCorrectChildren(): void
    {
        $entities = $this->createMockEntities();
        $root1Id = 1;
        $childEntities = [$entities['child1_1'], $entities['child1_2']];

        // 验证 JOIN 和 WHERE 条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('join')
            ->with('e.parent', 'p')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('p.id = :parentId')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('parentId', $root1Id)
        ;

        // 配置 query mock 返回子节点实体
        $this->query
            ->method('getResult')
            ->willReturn($childEntities)
        ;

        $children = $this->provider->getChildrenNodes($root1Id);

        $this->assertCount(2, $children);

        foreach ($children as $child) {
            $this->assertEquals($root1Id, $child->getParentId());
        }
    }

    public function testGetChildrenNodesReturnsEmptyForLeafNode(): void
    {
        $leafNodeId = 6; // grandchild1_1_1 的 ID

        // 验证查询条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('join')
            ->with('e.parent', 'p')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('p.id = :parentId')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('parentId', $leafNodeId)
        ;

        // 配置 query mock 返回空数组（叶子节点没有子节点）
        $this->query
            ->method('getResult')
            ->willReturn([])
        ;

        $children = $this->provider->getChildrenNodes($leafNodeId);

        $this->assertEmpty($children);
    }

    public function testGetChildrenNodesReturnsEmptyForNonExistentParent(): void
    {
        $nonExistentId = 99999;

        // 验证查询条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('join')
            ->with('e.parent', 'p')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('p.id = :parentId')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('parentId', $nonExistentId)
        ;

        // 配置 query mock 返回空数组
        $this->query
            ->method('getResult')
            ->willReturn([])
        ;

        $children = $this->provider->getChildrenNodes($nonExistentId);

        $this->assertEmpty($children);
    }

    public function testFindNodeByIdFindsCorrectEntity(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);
        $rootId = 1;

        // 配置 query mock 返回所有实体（findNodeById 通过 getTreeData 查找）
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $node = $this->provider->findNodeById($rootId);

        $this->assertNotNull($node);
        $this->assertEquals($rootId, $node->getId());
        $this->assertEquals('Root Category 1', $node->getLabel());
    }

    public function testFindNodeByIdReturnsNullForNonExistent(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $node = $this->provider->findNodeById(99999);

        $this->assertNull($node);
    }

    public function testFindNodesByIdsReturnsCorrectNodes(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);
        $ids = [1, 2]; // root1 和 root2 的 ID

        // 配置 query mock 返回所有实体（findNodesByIds 通过 getTreeData 查找）
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $nodes = $this->provider->findNodesByIds($ids);

        $this->assertCount(2, $nodes);

        $foundIds = array_map(static fn ($node) => $node->getId(), $nodes);
        $this->assertEquals($ids, $foundIds);
    }

    public function testFindNodesByIdsIgnoresNonExistentIds(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);
        $validId = 1;
        $ids = [$validId, 99999, 88888];

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $nodes = $this->provider->findNodesByIds($ids);

        $this->assertCount(1, $nodes);
        $this->assertEquals($validId, $nodes[0]->getId());
    }

    public function testSearchNodesFindsMatchingEntities(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 配置 query mock 返回所有实体（searchNodes 通过 getTreeData 搜索）
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $results = $this->provider->searchNodes('Child');

        $this->assertGreaterThanOrEqual(3, count($results));

        foreach ($results as $node) {
            $this->assertStringContainsString('Child', $node->getLabel());
        }
    }

    public function testSearchNodesIsCaseInsensitive(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $results = $this->provider->searchNodes('child');

        $this->assertGreaterThanOrEqual(3, count($results));
    }

    public function testSearchNodesReturnsEmptyForNoMatches(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $results = $this->provider->searchNodes('NonExistentTerm');

        $this->assertEmpty($results);
    }

    public function testSearchNodesWithOptions(): void
    {
        $entities = $this->createMockEntities();
        $activeEntities = array_filter($entities, fn ($entity) => true === (property_exists($entity, 'active') && $entity->active));

        $options = [
            'where' => ['active' => true],
        ];

        // 验证过滤条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with('e.active = :active')
        ;

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('active', true)
        ;

        // 配置 query mock 返回激活的实体
        $this->query
            ->method('getResult')
            ->willReturn(array_values($activeEntities))
        ;

        $results = $this->provider->searchNodes('Child', $options);

        $this->assertGreaterThanOrEqual(2, count($results));

        foreach ($results as $node) {
            $this->assertStringContainsString('Child', $node->getLabel());
            // 验证实体确实是激活的
            $entity = $node->getMetadata()['entity'];
            $this->assertIsObject($entity);
            $this->assertTrue(property_exists($entity, 'active') && true === $entity->active);
        }
    }

    public function testConvertEntitiesToNodesCreatesCorrectNodes(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 使用反射访问受保护的方法
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('convertEntitiesToNodes');
        $method->setAccessible(true);

        $nodes = $method->invoke($this->provider, $allEntities);

        $this->assertIsArray($nodes);
        $this->assertCount(count($allEntities), $nodes);

        foreach ($nodes as $node) {
            $this->assertIsObject($node);
            $this->assertTrue(method_exists($node, 'getLabel'));
            $this->assertTrue(method_exists($node, 'getMetadata'));
            $this->assertNotEmpty($node->getLabel());
            $metadata = $node->getMetadata();
            $this->assertArrayHasKey('entity', $metadata);
            $entity = $metadata['entity'];
            $this->assertIsObject($entity);
        }
    }

    public function testEntityTreeDataProviderWithDifferentLabelField(): void
    {
        $entities = $this->createMockEntities();
        $rootEntities = [$entities['root1'], $entities['root2']];

        // 创建使用 description 作为标签的 provider
        $provider = new EntityTreeDataProvider(
            $this->entityManager,
            $this->entityClass,
            [
                'id_field' => 'id',
                'label_field' => 'description', // 使用 description 作为标签
                'parent_field' => 'parent',
            ]
        );

        // 验证 WHERE 条件被正确应用
        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('e.parent IS NULL')
        ;

        // 配置 query mock 返回根节点实体
        $this->query
            ->method('getResult')
            ->willReturn($rootEntities)
        ;

        $roots = $provider->getRootNodes();

        $this->assertCount(2, $roots);
        $this->assertEquals('First root category', $roots[0]->getLabel());
        $this->assertEquals('Second root category', $roots[1]->getLabel());
    }

    public function testEntityTreeDataProviderWithoutParentField(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 创建不使用父子关系的 provider
        $provider = new EntityTreeDataProvider(
            $this->entityManager,
            $this->entityClass,
            [
                'id_field' => 'id',
                'label_field' => 'name',
                'parent_field' => null, // 不使用父子关系
            ]
        );

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $tree = $provider->getTreeData();

        // 所有实体都应该作为根节点返回
        $this->assertGreaterThanOrEqual(2, count($tree));

        $roots = $provider->getRootNodes();
        // getRootNodes 在没有 parent_field 时应该返回所有实体
        $this->assertGreaterThanOrEqual(2, count($roots));
    }

    public function testBuildTreeSetsCorrectLevels(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $tree = $this->provider->getTreeData();

        // 验证级别设置
        $this->assertEquals(0, $tree[0]->getLevel());
        $this->assertEquals(1, $tree[0]->getChildren()[0]->getLevel());
        $this->assertEquals(2, $tree[0]->getChildren()[0]->getChildren()[0]->getLevel());
    }

    public function testBuildTreeWithExpandedLevelOption(): void
    {
        $entities = $this->createMockEntities();
        $allEntities = array_values($entities);

        // 创建带有展开级别选项的 provider
        $provider = new EntityTreeDataProvider(
            $this->entityManager,
            $this->entityClass,
            [
                'id_field' => 'id',
                'label_field' => 'name',
                'parent_field' => 'parent',
                'expanded_level' => 2,
            ]
        );

        // 配置 query mock 返回所有实体
        $this->query
            ->method('getResult')
            ->willReturn($allEntities)
        ;

        $tree = $provider->getTreeData();

        // 验证展开状态
        $this->assertTrue($tree[0]->isExpanded()); // 级别 0
        $this->assertTrue($tree[0]->getChildren()[0]->isExpanded()); // 级别 1
        $this->assertFalse($tree[0]->getChildren()[0]->getChildren()[0]->isExpanded()); // 级别 2
    }
}
