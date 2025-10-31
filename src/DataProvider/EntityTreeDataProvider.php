<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNode;
use Tourze\EasyAdminTreeSelectFieldBundle\Model\TreeNodeInterface;

/**
 * Entity数据提供者
 */
class EntityTreeDataProvider extends AbstractTreeDataProvider
{
    private EntityManagerInterface $entityManager;

    /** @var class-string<object> */
    private string $entityClass;

    private string $idField;

    private string $labelField;

    private ?string $parentField;

    /**
     * @param class-string<object> $entityClass
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $entityClass,
        array $options = [],
    ) {
        parent::__construct($options);

        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
        $this->idField = is_string($options['id_field'] ?? null) ? $options['id_field'] : 'id';
        $this->labelField = is_string($options['label_field'] ?? null) ? $options['label_field'] : 'name';
        $this->parentField = is_string($options['parent_field'] ?? null) ? $options['parent_field'] : null;
    }

    /**
     * @param array<string, mixed> $options
     * @return TreeNodeInterface[]
     */
    public function getTreeData(array $options = []): array
    {
        $repository = $this->entityManager->getRepository($this->entityClass);

        $qb = $repository->createQueryBuilder('e');

        $this->applyWhereConditions($qb, $options);
        $this->applyOrderByConditions($qb, $options);

        $entities = $qb->getQuery()->getResult();
        /** @var object[] $entities */
        $nodes = $this->convertEntitiesToNodes($entities);

        return $this->buildTree($nodes);
    }

    /**
     * 将实体转换为树节点
     */
    /**
     * @param object[] $entities
     * @return TreeNodeInterface[]
     */
    protected function convertEntitiesToNodes(array $entities): array
    {
        $nodes = [];
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($entities as $entity) {
            $id = $accessor->getValue($entity, $this->idField);
            $labelValue = $accessor->getValue($entity, $this->labelField);
            $label = is_scalar($labelValue) ? (string) $labelValue : '';

            $parentId = null;
            if (null !== $this->parentField) {
                $parent = $accessor->getValue($entity, $this->parentField);
                if (null !== $parent && is_object($parent)) {
                    $parentId = $accessor->getValue($parent, $this->idField);
                }
            }

            $node = new TreeNode($id, $label, $parentId);

            // 添加实体作为元数据
            $node->setMetadata(['entity' => $entity]);

            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * @return TreeNodeInterface[]
     */
    public function getRootNodes(): array
    {
        $repository = $this->entityManager->getRepository($this->entityClass);

        $qb = $repository->createQueryBuilder('e');

        if (null !== $this->parentField) {
            $qb->where("e.{$this->parentField} IS NULL");
        }

        $entities = $qb->getQuery()->getResult();
        /** @var object[] $entities */

        return $this->convertEntitiesToNodes($entities);
    }

    /**
     * 应用WHERE条件到QueryBuilder
     * @param array<string, mixed> $options
     */
    private function applyWhereConditions(QueryBuilder $qb, array $options): void
    {
        if (!isset($options['where']) || !is_array($options['where'])) {
            return;
        }

        foreach ($options['where'] as $field => $value) {
            if (is_string($field)) {
                $qb->andWhere("e.{$field} = :{$field}")
                    ->setParameter($field, $value)
                ;
            }
        }
    }

    /**
     * 应用ORDER BY条件到QueryBuilder
     * @param array<string, mixed> $options
     */
    private function applyOrderByConditions(QueryBuilder $qb, array $options): void
    {
        if (!isset($options['order_by']) || !is_array($options['order_by'])) {
            return;
        }

        foreach ($options['order_by'] as $field => $direction) {
            if (is_string($field) && is_string($direction)) {
                $qb->addOrderBy("e.{$field}", $direction);
            }
        }
    }

    /**
     * @return TreeNodeInterface[]
     */
    public function getChildrenNodes(mixed $parentId): array
    {
        $repository = $this->entityManager->getRepository($this->entityClass);

        $qb = $repository->createQueryBuilder('e');

        if (null !== $this->parentField) {
            $qb->join("e.{$this->parentField}", 'p')
                ->where("p.{$this->idField} = :parentId")
                ->setParameter('parentId', $parentId)
            ;
        }

        $entities = $qb->getQuery()->getResult();
        /** @var object[] $entities */

        return $this->convertEntitiesToNodes($entities);
    }
}
