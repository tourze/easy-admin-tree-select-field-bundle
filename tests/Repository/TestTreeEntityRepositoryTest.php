<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * TestTreeEntityRepository 测试类
 * @internal
 */
#[CoversClass(TestTreeEntityRepository::class)]
#[RunTestsInSeparateProcesses]
class TestTreeEntityRepositoryTest extends AbstractIntegrationTestCase
{
    private TestTreeEntityRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(TestTreeEntity::class);
        $this->assertInstanceOf(TestTreeEntityRepository::class, $repository);
        $this->repository = $repository;

        // 加载测试数据
        $this->loadFixtures();
    }

    protected function onTearDown(): void
    {
        parent::onTearDown();
        // EntityManager 的清理由 AbstractIntegrationTestCase 自动处理
    }

    private function loadFixtures(): void
    {
        $entityManager = self::getEntityManager();

        // 直接创建测试数据，不使用Fixtures
        $electronics = new TestTreeEntity();
        $electronics->setName('电子产品');
        $electronics->setDescription('所有电子产品分类');
        $electronics->setActive(true);
        $electronics->setSortOrder(1);

        $books = new TestTreeEntity();
        $books->setName('图书');
        $books->setDescription('各类图书分类');
        $books->setActive(true);
        $books->setSortOrder(2);

        $clothing = new TestTreeEntity();
        $clothing->setName('服装');
        $clothing->setDescription('各类服装分类');
        $clothing->setActive(true);
        $clothing->setSortOrder(3);

        $computers = new TestTreeEntity();
        $computers->setName('电脑');
        $computers->setDescription('各类电脑设备');
        $computers->setActive(true);
        $computers->setSortOrder(1);
        $computers->setParent($electronics);

        $phones = new TestTreeEntity();
        $phones->setName('手机');
        $phones->setDescription('各类手机设备');
        $phones->setActive(true);
        $phones->setSortOrder(2);
        $phones->setParent($electronics);

        $laptop = new TestTreeEntity();
        $laptop->setName('笔记本电脑');
        $laptop->setDescription('笔记本电脑分类');
        $laptop->setActive(true);
        $laptop->setSortOrder(1);
        $laptop->setParent($computers);

        $entityManager->persist($electronics);
        $entityManager->persist($books);
        $entityManager->persist($clothing);
        $entityManager->persist($computers);
        $entityManager->persist($phones);
        $entityManager->persist($laptop);

        $entityManager->flush();
    }

    public function testFindRootNodes(): void
    {
        $rootNodes = $this->repository->findRootNodes();

        $this->assertGreaterThan(0, count($rootNodes));

        foreach ($rootNodes as $node) {
            $this->assertInstanceOf(TestTreeEntity::class, $node);
            $this->assertNull($node->getParent());
        }

        // 验证排序
        $names = array_map(fn ($node) => $node->getName(), $rootNodes);
        $this->assertContains('电子产品', $names);
        $this->assertContains('图书', $names);
        $this->assertContains('服装', $names);
    }

    public function testFindDirectChildren(): void
    {
        $rootNodes = $this->repository->findRootNodes();
        $electronicsNode = null;

        foreach ($rootNodes as $node) {
            if ('电子产品' === $node->getName()) {
                $electronicsNode = $node;
                break;
            }
        }

        $this->assertNotNull($electronicsNode);

        $children = $this->repository->findDirectChildren($electronicsNode);

        $this->assertGreaterThan(0, count($children));

        foreach ($children as $child) {
            $this->assertInstanceOf(TestTreeEntity::class, $child);
            $this->assertSame($electronicsNode, $child->getParent());
        }

        $childNames = array_map(fn ($child) => $child->getName(), $children);
        $this->assertContains('电脑', $childNames);
        $this->assertContains('手机', $childNames);
    }

    public function testFindActiveRootNodes(): void
    {
        $activeRootNodes = $this->repository->findActiveRootNodes();

        $this->assertGreaterThan(0, count($activeRootNodes));

        foreach ($activeRootNodes as $node) {
            $this->assertInstanceOf(TestTreeEntity::class, $node);
            $this->assertTrue($node->isActive());
            $this->assertNull($node->getParent());
        }
    }

    public function testFindByNameLike(): void
    {
        $nodes = $this->repository->findByNameLike('电');

        $this->assertGreaterThan(0, count($nodes));

        foreach ($nodes as $node) {
            $this->assertInstanceOf(TestTreeEntity::class, $node);
            $this->assertStringContainsString('电', $node->getName());
        }
    }

    public function testGetTreeStructure(): void
    {
        $treeStructure = $this->repository->getTreeStructure();

        $this->assertIsArray($treeStructure);
        $this->assertGreaterThan(0, count($treeStructure));

        foreach ($treeStructure as $rootNode) {
            $this->assertIsArray($rootNode);
            $this->assertArrayHasKey('id', $rootNode);
            $this->assertArrayHasKey('name', $rootNode);
            $this->assertArrayHasKey('children', $rootNode);
            $this->assertIsArray($rootNode['children']);
        }
    }

    public function testGetNodeDepth(): void
    {
        // 查找一个深层节点
        $nodes = $this->repository->findByNameLike('笔记本电脑');

        if ([] !== $nodes) {
            $laptopNode = $nodes[0];
            $depth = $this->repository->getNodeDepth($laptopNode);

            $this->assertGreaterThanOrEqual(2, $depth); // 笔记本电脑应该至少在第2层
        } else {
            self::markTestSkipped('未找到笔记本电脑节点');
        }
    }

    public function testIsLeafNode(): void
    {
        // 查找叶子节点
        $nodes = $this->repository->findByNameLike('笔记本电脑');

        if ([] !== $nodes) {
            $laptopNode = $nodes[0];
            $isLeaf = $this->repository->isLeafNode($laptopNode);

            $this->assertTrue($isLeaf); // 笔记本电脑应该是叶子节点
        }

        // 查找非叶子节点
        $rootNodes = $this->repository->findRootNodes();
        if ([] !== $rootNodes) {
            $rootNode = $rootNodes[0];
            $isLeaf = $this->repository->isLeafNode($rootNode);

            $this->assertFalse($isLeaf); // 根节点不应该是叶子节点
        }
    }

    public function testGetChildrenCount(): void
    {
        $rootNodes = $this->repository->findRootNodes();

        if ([] !== $rootNodes) {
            $rootNode = $rootNodes[0];
            $childCount = $this->repository->getChildrenCount($rootNode);

            $this->assertGreaterThan(0, $childCount);
        }
    }

    public function testGetMaxDepth(): void
    {
        $maxDepth = $this->repository->getMaxDepth();

        $this->assertGreaterThanOrEqual(0, $maxDepth);
    }

    public function testSaveAndRemove(): void
    {
        // 测试保存
        $newEntity = new TestTreeEntity();
        $newEntity->setName('测试节点');
        $newEntity->setDescription('这是一个测试节点');
        $newEntity->setActive(true);
        $newEntity->setSortOrder(999);

        $this->repository->save($newEntity, true);

        $this->assertNotNull($newEntity->getId());

        // 测试查找
        $entityId = $newEntity->getId();
        $this->assertNotNull($entityId);
        $foundEntity = $this->repository->find($entityId);
        $this->assertNotNull($foundEntity);
        $this->assertSame('测试节点', $foundEntity->getName());

        // 测试删除
        $this->repository->remove($newEntity, true);

        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity);
    }

    public function testFindPath(): void
    {
        // 查找一个深层节点
        $nodes = $this->repository->findByNameLike('笔记本电脑');

        if ([] !== $nodes) {
            $laptopNode = $nodes[0];
            $path = $this->repository->findPath($laptopNode);

            $this->assertIsArray($path);
            $this->assertGreaterThan(1, count($path)); // 路径应该包含多个节点

            // 验证路径的最后一个节点就是目标节点
            $lastNode = end($path);
            $this->assertNotFalse($lastNode);
            $this->assertSame($laptopNode->getId(), $lastNode->getId());

            // 验证路径的第一个节点是根节点
            $firstNode = reset($path);
            $this->assertNotFalse($firstNode);
            $this->assertNull($firstNode->getParent());
        } else {
            self::markTestSkipped('未找到笔记本电脑节点');
        }
    }

    /**
     * 测试查找一个存在的实体。
     */
    public function testFindWithExistingIdShouldReturnEntity(): void
    {
        // 获取一个已知的根节点
        $rootNodes = $this->repository->findRootNodes();
        $this->assertNotEmpty($rootNodes);

        $existingEntity = $rootNodes[0];
        $entityId = $existingEntity->getId();
        $this->assertNotNull($entityId);

        // 使用该 ID 调用 find() 方法
        $foundEntity = $this->repository->find($entityId);

        // 断言返回值不为 null
        $this->assertNotNull($foundEntity);

        // 断言返回的对象是正确的实体（Entity）类的实例
        $this->assertInstanceOf(TestTreeEntity::class, $foundEntity);

        // 断言返回实体的 ID 与查询的 ID 一致
        $this->assertSame($entityId, $foundEntity->getId());
    }

    /**
     * 测试 createBaseQueryBuilder 方法
     */
    public function testCreateBaseQueryBuilder(): void
    {
        $queryBuilder = $this->repository->createBaseQueryBuilder();

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);

        // 验证查询是否包含正确的排序
        $dql = $queryBuilder->getDQL();
        $this->assertStringContainsString('ORDER BY', $dql);
        $this->assertStringContainsString('sortOrder', $dql);
    }

    /**
     * 测试 findActiveNodes 方法
     */
    public function testFindActiveNodes(): void
    {
        $activeNodes = $this->repository->findActiveNodes();

        $this->assertIsArray($activeNodes);

        foreach ($activeNodes as $node) {
            $this->assertInstanceOf(TestTreeEntity::class, $node);
            $this->assertTrue($node->isActive());
        }
    }

    /**
     * 测试 findAllDescendants 方法
     */
    public function testFindAllDescendants(): void
    {
        $rootNodes = $this->repository->findRootNodes();
        $this->assertNotEmpty($rootNodes);

        $rootNode = $rootNodes[0];
        $descendants = $this->repository->findAllDescendants($rootNode);

        $this->assertIsArray($descendants);

        foreach ($descendants as $descendant) {
            $this->assertInstanceOf(TestTreeEntity::class, $descendant);
            // 验证找到的确实是子孙节点
            $this->assertNotSame($rootNode->getId(), $descendant->getId());
        }
    }

    /**
     * 测试 findByDepth 方法
     */
    public function testFindByDepth(): void
    {
        $depth0Nodes = $this->repository->findByDepth(0);
        $this->assertIsArray($depth0Nodes);

        foreach ($depth0Nodes as $node) {
            $this->assertInstanceOf(TestTreeEntity::class, $node);
            $this->assertNull($node->getParent());
        }
    }

    /**
     * 测试 remove 方法
     */
    public function testRemove(): void
    {
        // 创建一个新实体用于删除测试
        $newEntity = new TestTreeEntity();
        $newEntity->setName('待删除节点');
        $newEntity->setActive(true);
        $newEntity->setSortOrder(999);

        $this->repository->save($newEntity, true);
        $entityId = $newEntity->getId();
        $this->assertNotNull($entityId);

        // 验证实体存在
        $foundEntity = $this->repository->find($entityId);
        $this->assertNotNull($foundEntity);

        // 删除实体
        $this->repository->remove($newEntity, true);

        // 验证实体已被删除
        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity);
    }
}
