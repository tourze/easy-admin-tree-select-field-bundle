<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\EasyAdminTreeSelectFieldBundle\Entity\TestTreeEntity;
use Tourze\EasyAdminTreeSelectFieldBundle\Tests\Contract\TestTreeEntityRepositoryContract;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * TestTreeEntity 仓储类
 * 提供树形数据的查询方法
 *
 * @extends ServiceEntityRepository<TestTreeEntity>
 */
#[AsRepository(entityClass: TestTreeEntity::class)]
class TestTreeEntityRepository extends ServiceEntityRepository implements TestTreeEntityRepositoryContract
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestTreeEntity::class);
    }

    /**
     * 查找所有根节点（无父节点的节点）
     *
     * @return array<TestTreeEntity>
     */
    public function findRootNodes(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.parent IS NULL')
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<TestTreeEntity> $result */
    }

    /**
     * 查找指定节点的直接子节点
     *
     * @return array<TestTreeEntity>
     */
    public function findDirectChildren(TestTreeEntity $parent): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<TestTreeEntity> $result */
    }

    /**
     * 查找指定节点的所有子孙节点（递归）
     *
     * @return array<TestTreeEntity>
     */
    public function findAllDescendants(TestTreeEntity $parent): array
    {
        return $this->collectDescendants($parent);
    }

    /**
     * 递归收集所有子孙节点
     * @return array<TestTreeEntity>
     */
    private function collectDescendants(TestTreeEntity $parent): array
    {
        $descendants = [];
        $children = $this->findDirectChildren($parent);

        foreach ($children as $child) {
            $descendants[] = $child;
            $childDescendants = $this->collectDescendants($child);
            $descendants = array_merge($descendants, $childDescendants);
        }

        return $descendants;
    }

    /**
     * 查找指定节点的路径（从根节点到指定节点）
     *
     * @return array<TestTreeEntity>
     */
    public function findPath(TestTreeEntity $node): array
    {
        $path = [];
        $current = $node;

        // 向上追溯到根节点
        while (null !== $current) {
            array_unshift($path, $current);
            $current = $current->getParent();
        }

        return $path;
    }

    /**
     * 查找指定深度的所有节点
     *
     * @return array<TestTreeEntity>
     */
    public function findByDepth(int $depth): array
    {
        if (0 === $depth) {
            return $this->findRootNodes();
        }

        // 使用递归CTE查询指定深度的节点
        $sql = '
            WITH RECURSIVE tree_with_depth AS (
                SELECT id, name, parent_id, description, active, sort_order, 0 as depth
                FROM test_tree_entity 
                WHERE parent_id IS NULL
                
                UNION ALL
                
                SELECT t.id, t.name, t.parent_id, t.description, t.active, t.sort_order, twd.depth + 1
                FROM test_tree_entity t
                INNER JOIN tree_with_depth twd ON t.parent_id = twd.id
                WHERE twd.depth < :max_depth
            )
            SELECT * FROM tree_with_depth 
            WHERE depth = :target_depth
            ORDER BY sort_order, name
        ';

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('max_depth', 1000);
        $stmt->bindValue('target_depth', $depth);
        $result = $stmt->executeQuery();

        $nodes = [];
        while ($row = $result->fetchAssociative()) {
            $entity = $this->find($row['id']);
            if (null !== $entity) {
                $nodes[] = $entity;
            }
        }

        return $nodes;
    }

    /**
     * 查找活跃的节点
     *
     * @return array<TestTreeEntity>
     */
    public function findActiveNodes(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.active = :active')
            ->setParameter('active', true)
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<TestTreeEntity> $result */
    }

    /**
     * 查找活跃的根节点
     *
     * @return array<TestTreeEntity>
     */
    public function findActiveRootNodes(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.parent IS NULL')
            ->andWhere('t.active = :active')
            ->setParameter('active', true)
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<TestTreeEntity> $result */
    }

    /**
     * 按名称搜索节点
     *
     * @return array<TestTreeEntity>
     */
    public function findByNameLike(string $name): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<TestTreeEntity> $result */
    }

    /**
     * 获取树形结构的完整数据（层级格式）
     *
     * @return array<mixed>
     */
    public function getTreeStructure(): array
    {
        $roots = $this->findActiveRootNodes();
        $tree = [];

        foreach ($roots as $root) {
            $tree[] = $this->buildTreeNode($root);
        }

        return $tree;
    }

    /**
     * 构建单个树节点（递归）
     *
     * @return array<mixed>
     */
    private function buildTreeNode(TestTreeEntity $node): array
    {
        $nodeData = [
            'id' => $node->getId(),
            'name' => $node->getName(),
            'description' => $node->getDescription(),
            'active' => $node->isActive(),
            'sortOrder' => $node->getSortOrder(),
            'children' => [],
        ];

        $children = $this->findDirectChildren($node);
        foreach ($children as $child) {
            if ($child->isActive()) {
                $nodeData['children'][] = $this->buildTreeNode($child);
            }
        }

        return $nodeData;
    }

    /**
     * 获取节点的层级数（深度）
     */
    public function getNodeDepth(TestTreeEntity $node): int
    {
        $depth = 0;
        $current = $node->getParent();

        while (null !== $current) {
            ++$depth;
            $current = $current->getParent();
        }

        return $depth;
    }

    /**
     * 检查节点是否为叶子节点
     */
    public function isLeafNode(TestTreeEntity $node): bool
    {
        $childCount = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.parent = :parent')
            ->setParameter('parent', $node)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return 0 === $childCount;
    }

    /**
     * 获取节点的子节点数量
     */
    public function getChildrenCount(TestTreeEntity $node): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.parent = :parent')
            ->setParameter('parent', $node)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 获取树的最大深度
     */
    public function getMaxDepth(): int
    {
        // 使用递归CTE计算最大深度
        $sql = '
            WITH RECURSIVE tree_depth AS (
                SELECT id, 0 as depth
                FROM test_tree_entity 
                WHERE parent_id IS NULL
                
                UNION ALL
                
                SELECT t.id, td.depth + 1
                FROM test_tree_entity t
                INNER JOIN tree_depth td ON t.parent_id = td.id
            )
            SELECT MAX(depth) as max_depth FROM tree_depth
        ';

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql)->fetchOne();

        return is_numeric($result) ? (int) $result : 0;
    }

    /**
     * 创建基础查询构建器
     */
    public function createBaseQueryBuilder(string $alias = 't'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->orderBy($alias . '.sortOrder', 'ASC')
            ->addOrderBy($alias . '.name', 'ASC')
        ;
    }

    /**
     * 保存实体
     */
    public function save(TestTreeEntity $entity, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();

        // 如果有parent且parent没有被persist，先persist parent
        $parent = $entity->getParent();
        if (null !== $parent && !$entityManager->contains($parent)) {
            $entityManager->persist($parent);
        }

        $entityManager->persist($entity);

        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(TestTreeEntity $entity, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();

        // 只有当实体有ID时才进行删除操作
        if (null === $entity->getId()) {
            return;
        }

        // 先删除所有子节点（如果有的话）
        $children = $this->findDirectChildren($entity);
        foreach ($children as $child) {
            $this->remove($child, false); // 递归删除，但不立即flush
        }

        $entityManager->remove($entity);

        if ($flush) {
            $entityManager->flush();
        }
    }
}
