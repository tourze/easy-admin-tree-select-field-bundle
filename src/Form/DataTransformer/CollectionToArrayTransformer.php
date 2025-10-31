<?php

declare(strict_types=1);

namespace Tourze\EasyAdminTreeSelectFieldBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * 将 Collection 转换为 Array，反之亦然
 * 用于处理 EasyAdmin 中 Collection 类型字段的表单数据转换
 *
 * @implements DataTransformerInterface<Collection<int, mixed>, array<mixed>>
 */
class CollectionToArrayTransformer implements DataTransformerInterface
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $entityClass,
    ) {
    }

    /**
     * 将 Collection 转换为数组（Model -> View）
     *
     * @param mixed $value
     * @return array<mixed>
     */
    public function transform($value): array
    {
        if (null === $value || !$value instanceof Collection) {
            return [];
        }

        // 将实体对象转换为 ID 数组，用于表单验证
        $result = [];
        foreach ($value as $item) {
            if (is_object($item) && method_exists($item, 'getId')) {
                $result[] = $item->getId();
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * 将数组转换为 Collection（View -> Model）
     *
     * @param mixed $value
     * @return Collection<int, mixed>
     */
    public function reverseTransform($value): Collection
    {
        if (!is_array($value)) {
            return new ArrayCollection();
        }

        // 将 ID 数组转换为实体对象 Collection
        $entities = [];
        foreach ($value as $id) {
            if (null !== $id && '' !== $id) {
                $entity = $this->entityManager->getRepository($this->entityClass)->find($id);
                if (null !== $entity) {
                    $entities[] = $entity;
                }
            }
        }

        return new ArrayCollection($entities);
    }
}
